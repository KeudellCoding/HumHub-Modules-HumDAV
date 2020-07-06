# HumDAV
HumHub module for external access to e.g. the contacts over the CardDAV protocol.

## Requirements
- Make sure HumHub URL Rewriting is enabled on your installation (https://docs.humhub.org/docs/admin/installation#pretty-urls)

## Installation
- Navigate to the folder "\protected\modules".
- Execute the command "git clone https://github.com/KeudellCoding/HumHub-Modules-HumDAV".
- If the module does not appear directly in the module list, you can empty the cache.
- Configure the module.

## Access
- Two address books are automatically created for each user.
- The URL is organized as follows:
  - All Users: **{domain}/humdav/remote/addressbooks/{username}/main/**
  - Following Users: **{domain}/humdav/remote/addressbooks/{username}/following/**
- The registration is currently only secured via Basic Auth. Simply enter your HumHub username and password here.
- Later, better authentication methods are planned.
