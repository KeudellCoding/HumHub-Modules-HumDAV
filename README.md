# HumDAV
HumHub module for external access to e.g. the contacts over the CardDAV protocol.

## Important note:
- Currently there are still internal server errors on my production system, but these only affect CardDAV access.
- On my test system (XAMPP, Windows) everything runs without problems.
- If someone knows where the mistake could be, I would be very grateful if I could get a hint.

## Requirements
- Make sure HumHub URL Rewriting is enabled on your installation (https://docs.humhub.org/docs/admin/installation#pretty-urls)

## Access
- Two address books are automatically created for each user.
- The URL is organized as follows:
  - All Users: **{domain}/humdav/remote/addressbooks/{username}/main/**
  - Following Users: **{domain}/humdav/remote/addressbooks/{username}/following/**
- The registration is currently only secured via Basic Auth. Simply enter your HumHub username and password here.
- Later, better authentication methods are planned.
