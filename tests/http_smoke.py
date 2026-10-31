"""Exercise an enabled HumDAV installation using temporary, automatically revoked tokens."""

import base64
from html.parser import HTMLParser
from http.cookiejar import CookieJar
import os
import plistlib
import re
import unittest
from urllib.error import HTTPError
from urllib.parse import urlencode, urljoin, urlsplit
from urllib.request import HTTPCookieProcessor, HTTPRedirectHandler, Request, build_opener
import uuid
import xml.etree.ElementTree as ET


class Forms(HTMLParser):
    def __init__(self, html):
        super().__init__()
        self.forms = []
        self.current = None
        self.inputs = []
        self.feed(html)

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == "input":
            self.inputs.append(attrs)
        if tag == "form":
            self.current = {"action": attrs.get("action", ""), "fields": {}}
            self.forms.append(self.current)
        elif tag == "input" and self.current is not None and attrs.get("name"):
            if attrs.get("type") in ("checkbox", "radio") and "checked" not in attrs:
                return
            self.current["fields"][attrs["name"]] = attrs.get("value", "")

    def handle_endtag(self, tag):
        if tag == "form":
            self.current = None

    def containing(self, field):
        return next(form for form in self.forms if field in form["fields"])


class NoRedirect(HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


class HttpSmokeTest(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.base = os.environ.get("HUMDAV_TEST_URL", "http://localhost:8100").rstrip("/")
        cls.username = os.environ.get("HUMDAV_TEST_USERNAME", "admin")
        cls.password = os.environ["HUMDAV_TEST_PASSWORD"]
        cls.browser = build_opener(HTTPCookieProcessor(CookieJar()))
        cls.tokens = []
        status, _, html, url = cls.fetch("/user/auth/login")
        form = Forms(html.decode()).containing("Login[username]")
        form["fields"]["Login[username]"] = cls.username
        status, _, html, url = cls.fetch(form["action"] or url, form["fields"])
        form = Forms(html.decode()).containing("Login[password]")
        form["fields"]["Login[password]"] = cls.password
        status, _, _, url = cls.fetch(form["action"] or url, form["fields"])
        if status != 200 or "/user/auth/" in url:
            raise RuntimeError("Could not log in to the test installation.")
        status, _, html, _ = cls.fetch("/humdav/accessinfo/index")
        if status != 200:
            raise RuntimeError("Enable HumDAV for the test user before running the HTTP tests.")
        cls.guid = re.search(rb"/principals/([a-f0-9-]+)", html).group(1).decode()
        cls.caldav_checkbox = next(item for item in Forms(html.decode()).inputs if item.get("id") == "mobileconfig-include-caldav")
        cls.calendar_enabled = "disabled" not in cls.caldav_checkbox

    @classmethod
    def fetch(cls, path, fields=None, method=None, headers=None, client=None, raw=None):
        data = urlencode(fields).encode() if fields is not None else raw
        req = Request(urljoin(cls.base + "/", path), data=data, method=method, headers=headers or {})
        try:
            response = (client or cls.browser).open(req, timeout=20)
        except HTTPError as error:
            response = error
        with response:
            return response.status, response.headers, response.read(), response.url

    @classmethod
    def token_ids(cls, name=None):
        params = {"sort": "-created_at"}
        if name:
            params["UserTokenSearch[name]"] = name
        status, _, html, _ = cls.fetch("/humdav/accessinfo/index?" + urlencode(params))
        if status != 200:
            raise RuntimeError("Could not read the token list.")
        return set(re.findall(rb"token-info\?id=(\d+)", html))

    @classmethod
    def revoke(cls, token_id):
        path = "/humdav/accessinfo/revoke-token?id=" + token_id.decode()
        status, _, html, url = cls.fetch(path)
        if status == 404:
            return
        if status != 200:
            raise RuntimeError("Could not open the test token revocation form.")
        form = Forms(html.decode()).containing("_csrf")
        form["fields"]["revoke-token-action"] = "revoke"
        status, _, _, _ = cls.fetch(form["action"] or url, form["fields"])
        if status != 200:
            raise RuntimeError("Could not revoke the temporary test token.")

    @classmethod
    def tearDownClass(cls):
        for token_id in cls.tokens:
            cls.revoke(token_id)

    def create_token(self):
        status, _, html, url = self.fetch("/humdav/accessinfo/generate-token")
        self.assertEqual(200, status)
        self.assertNotIn(b"used_for", html)
        self.assertNotIn(b"Used For", html)
        secret = re.search(rb"Your token: <b>([^<]+)</b>", html).group(1).decode()
        form = Forms(html.decode()).containing("UserToken[name]")
        name = "humdav-http-" + uuid.uuid4().hex
        form["fields"]["UserToken[name]"] = name
        status, _, _, _ = self.fetch(form["action"] or url, form["fields"])
        self.assertEqual(200, status)
        token_ids = self.token_ids(name)
        self.assertEqual(1, len(token_ids))
        token_id = next(iter(token_ids))
        self.tokens.append(token_id)
        return token_id, secret, form

    def dav(self, path, secret=None, method="PROPFIND", raw=None):
        headers = {"Depth": "1", "Content-Type": "application/xml", "User-Agent": 'HumDAV <test>&"'}
        if method == "GET" and path.endswith(".vcf"):
            headers["Accept"] = "text/vcard;version=4.0"
        if secret is not None:
            headers["Authorization"] = "Basic " + base64.b64encode((self.username + ":" + secret).encode()).decode()
        return self.fetch(path, method=method, headers=headers, client=build_opener(), raw=raw)

    def test_ui_and_discovery(self):
        status, _, html, _ = self.fetch("/humdav/admin/index")
        self.assertEqual(200, status)
        self.assertIn(b"EditForm[active]", html)
        status, _, html, _ = self.fetch("/humdav/accessinfo/index")
        self.assertEqual(200, status)
        self.assertEqual(4, html.count(b'data-bs-toggle="tab"'))
        self.assertNotIn(b'data-toggle="tab"', html)
        self.assertNotIn(b"used_for", html)
        self.assertNotIn(b"Used For", html)
        self.assertNotIn(b"iCal", html)
        status, headers, _, _ = self.fetch("/.well-known/carddav", client=build_opener(NoRedirect()))
        self.assertEqual(301, status)
        self.assertTrue(headers["Location"].endswith("/humdav/remote/"))
        status, headers, _, _ = self.fetch("/.well-known/caldav", client=build_opener(NoRedirect()))
        self.assertNotIn("/humdav/", headers.get("Location", ""))
        if self.calendar_enabled:
            self.assertIn(status, (301, 302, 307, 308))
            self.assertIn("checked", self.caldav_checkbox)
        before = self.token_ids()
        status, _, _, _ = self.fetch("/humdav/accessinfo/mobileconfig?target=invalid")
        self.assertEqual(400, status)
        self.assertEqual(before, self.token_ids())
        status, _, _, _ = self.fetch("/humdav/accessinfo/mobileconfig?target=ios&include_caldav=invalid")
        self.assertEqual(400, status)
        self.assertEqual(before, self.token_ids())

    def test_token_lifecycle_and_read_only_carddav(self):
        token_id, secret, draft_form = self.create_token()
        status, _, _, _ = self.fetch("/humdav/accessinfo/generate-token", draft_form["fields"])
        self.assertEqual(410, status)
        self.assertEqual(401, self.dav("/humdav/remote/")[0])
        self.assertEqual(401, self.dav("/humdav/remote/", "incorrect")[0])
        self.assertEqual(207, self.dav("/humdav/remote/", secret)[0])
        for collection in ("principals", "addressbooks"):
            self.assertEqual(207, self.dav("/humdav/remote/" + collection + "/" + self.guid + "/", secret)[0])
        self.assertEqual(404, self.dav("/humdav/remote/calendars/" + self.guid + "/", secret)[0])
        status, headers, _, _ = self.dav("/humdav/remote/ical/" + self.guid + "/0/" + secret, secret, "GET")
        self.assertEqual(404, status)
        self.assertNotIn("text/calendar", headers.get("Content-Type", ""))
        addressbook = "/humdav/remote/addressbooks/" + self.guid + "/allusers/"
        status, _, xml, _ = self.dav(addressbook, secret)
        self.assertEqual(207, status)
        ET.fromstring(xml)
        card = addressbook + self.guid + ".vcf"
        status, _, vcard, _ = self.dav(card, secret, "GET")
        self.assertEqual(200, status)
        self.assertIn(b"BEGIN:VCARD", vcard)
        self.assertIn(b"VERSION:4.0", vcard)
        self.assertEqual(403, self.dav(card, secret, "PUT", raw=vcard)[0])
        report = b'<c:addressbook-query xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:carddav"><d:prop><d:getetag/><c:address-data/></d:prop><c:filter/></c:addressbook-query>'
        status, _, xml, _ = self.dav(addressbook, secret, "REPORT", raw=report)
        self.assertEqual(207, status)
        self.assertIn(b"BEGIN:VCARD", xml)
        ET.fromstring(xml)
        status, _, html, url = self.fetch("/humdav/accessinfo/token-info?id=" + token_id.decode())
        self.assertEqual(200, status)
        self.assertIn(b"HumDAV &lt;test&gt;&amp;&quot;", html)
        self.assertNotIn(b"used_for", html)
        self.assertNotIn(b"Used For", html)
        form = Forms(html.decode()).containing("UserToken[name]")
        form["fields"]["UserToken[name]"] = "Renamed <HTTP test>"
        self.assertEqual(200, self.fetch(form["action"] or url, form["fields"])[0])
        self.revoke(token_id)
        self.assertEqual(401, self.dav("/humdav/remote/", secret)[0])

    def test_apple_profiles_provide_working_tokens(self):
        for target in ("ios", "osx"):
            before = self.token_ids()
            status, headers, content, _ = self.fetch("/humdav/accessinfo/mobileconfig?target=" + target)
            self.assertEqual(200, status)
            self.assertIn("application/x-apple-aspen-config", headers["Content-Type"])
            self.assertEqual("no-store", headers["Cache-Control"])
            new_ids = self.token_ids() - before
            self.assertEqual(1, len(new_ids))
            self.tokens.extend(new_ids)
            profile = plistlib.loads(content)
            carddav = next(payload for payload in profile["PayloadContent"] if payload["PayloadType"] == "com.apple.carddav.account")
            self.assertEqual(self.username, carddav["CardDAVUsername"])
            self.assertEqual(urlsplit(self.base).port or 80, carddav["CardDAVPort"])
            self.assertIn("/principals/" + self.guid, carddav["CardDAVPrincipalURL"])
            self.assertEqual(207, self.dav("/humdav/remote/", carddav["CardDAVPassword"])[0])
            calendars = [payload for payload in profile["PayloadContent"] if payload["PayloadType"] == "com.apple.caldav.account"]
            self.assertEqual(int(self.calendar_enabled), len(calendars))
            if calendars:
                caldav = calendars[0]
                self.assertNotEqual(carddav["CardDAVPassword"], caldav["CalDAVPassword"])
                self.assertNotIn("/humdav/", caldav["CalDAVPrincipalURL"])
                self.assertEqual(self.username, caldav["CalDAVUsername"])
                self.assertEqual(207, self.dav(caldav["CalDAVPrincipalURL"], caldav["CalDAVPassword"])[0])

    def test_caldav_can_be_excluded_from_both_apple_profiles(self):
        status, _, html, _ = self.fetch("/humdav/accessinfo/index")
        self.assertEqual(200, status)
        form = Forms(html.decode()).containing("include_caldav")
        for target in ("ios", "osx"):
            before = self.token_ids()
            fields = dict(form["fields"], target=target, include_caldav="0")
            status, _, content, _ = self.fetch(form["action"] + "?" + urlencode(fields))
            self.assertEqual(200, status)
            new_ids = self.token_ids() - before
            self.assertEqual(1, len(new_ids))
            self.tokens.extend(new_ids)
            payloads = plistlib.loads(content)["PayloadContent"]
            self.assertEqual(["com.apple.carddav.account"], [payload["PayloadType"] for payload in payloads])
            self.assertEqual(207, self.dav("/humdav/remote/", payloads[0]["CardDAVPassword"])[0])


if __name__ == "__main__":
    unittest.main(verbosity=2)
