Changelog
=========

1.6.0 (October 31, 2026)
----------------------
- Chng: Require HumHub 1.19 and PHP 8.2 or newer.
- Chng: Remove HumDAV's own CalDAV server and iCal subscriptions; use the Calendar module's native calendar services instead. Existing HumDAV calendar URLs no longer work.
- Chng: Migrate tokens to CardDAV only, delete obsolete iCal and other non-DAV tokens, and remove the "Used for" field. Existing CardDAV credentials remain valid.
- Enh: Include native Calendar CalDAV credentials and principal URLs in Apple profiles through an enabled-by-default checkbox. CardDAV is always included.
- Fix: Migrate forms, HTML helpers, buttons, menu entries, profile images and module activation checks to the HumHub 1.19 APIs, with Bootstrap 5 tabs and CSP-compatible clipboard handling.
- Fix: Use a stateless DAV identity for contact visibility, preserve base paths in CardDAV discovery, and leave CalDAV discovery to Calendar.
- Fix: Deliver escaped Apple profiles through HumHub responses, protect token owners and hashes from form assignment, handle expired token forms and escape token metadata.
- Enh: Update SabreDAV to 4.7.1 and its dependencies, with a Composer lock file.
- Enh: Add integration and HTTP smoke tests, including legacy token migration and optional native Calendar coverage.

1.5.1 (June 29, 2023)
----------------------
- Fix: Error when calling mobileconfig if the server manages the files case sensitive.
- Fix: On some systems, the inline script for generating the iCal address is rejected.

1.5.0 (June 26, 2023)
----------------------
- Fix: Write protection for calendar objects.
- Enh: Update Sabre/dav to 4.4.0
- Enh: Use Sabre/VObject to generate VCard
- Chng: Remove the "Enable Auto Discovery" function, as it is no longer needed.

1.4.1 (June 01, 2022)
----------------------
- Fix: The payload version for the automatic configuration files for iOS and macOS was wrong.

1.4.0 (May 24, 2022)
----------------------
- Enh: Adding a Token Authentication
- Enh: Adding an iCal interface
- Fix: (SECURITY) Users who should not have access can still access some information.
- Fix #2: If there are special characters in the user name, unexpected errors may occur. Unfortunately, all old URLs become invalid with this fix.
- Enh: On iOS devices, groups can be activated/deactivated (contacts can thus be displayed multiple times).
- Fix: Sabre/dav uses the deprecated libxml_disable_entity_loader function, which is no longer usable as of PHP version 8.0.
- Fix: If the calendar module was not activated/installed, errors could occur.

1.3.3 (May 14, 2022)
----------------------
- Fix: Removal of the problems related to the removal of the obsolete "Directory" module (since HumHub 1.11).

1.3.2 (October 28, 2021)
---------------------
- Enh: This module is now available in HumHum Marketplace.
- Chng: Removal of the update mechanism.
