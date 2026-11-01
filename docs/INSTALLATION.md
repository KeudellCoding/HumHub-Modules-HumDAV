# HumDAV installation and upgrades

## Requirements

- HumHub 1.19 or later and PHP 8.2 or later.
- Enable HumHub's pretty URLs / URL rewriting.
- Install and enable a compatible Calendar module for optional native CalDAV configuration. CardDAV works independently.

## Installation

Install HumDAV through the Marketplace or place the module in your custom module directory. The distribution includes `composer.json`, `composer.lock` and the required `vendor/` runtime files. When installing from source without dependencies, run `composer install --no-dev` in the module directory.

Enable HumDAV in the module administration, configure **Allowed users**, and review the optional export fields (all off by default). Open **HumDAV** in the menu to create a device token. Configure the menu location in the module settings if the link is hidden. Client setup is covered in the [manual](MANUAL.md).

## Upgrading to 1.6.0

Replace the module files and `vendor/` directory in place, then apply the pending HumDAV migrations through HumHub's updater. Existing settings and CardDAV credentials remain valid. The migration permanently deletes obsolete iCal and other non-DAV tokens and removes the **Used for** field.

HumDAV's own iCal and CalDAV endpoints are no longer available. Use the Calendar module's calendar exports and native CalDAV service. CalDAV clients using the former HumDAV endpoints must be configured for the native service or download a new Apple profile.

The stricter profile permissions and field visibility apply to every CardDAV request. Existing explicit export settings are retained; unset optional fields default to disabled. E-mail export is disabled until explicitly enabled and still requires a visible E-mail profile field.

Download Apple profiles using the buttons on the HumDAV page. Downloads use a POST form protected by HumHub CSRF validation; direct GET download links no longer create tokens.
