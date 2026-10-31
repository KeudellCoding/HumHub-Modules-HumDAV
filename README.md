# HumDAV

Read-only CardDAV contacts for HumHub, with optional native Calendar CalDAV configuration for Apple devices.

## Requirements

- HumHub 1.19 or later and PHP 8.2 or later.
- Enable HumHub's pretty URLs / URL rewriting.
- Install and enable a compatible Calendar module for optional native CalDAV configuration. CardDAV works independently.

## Installation and upgrades

Install HumDAV through the Marketplace or place the module in your custom module directory. With the Docker layout used in this project, its directory is `modules/humdav`.

The distribution includes its Composer dependencies. When installing from source, run `composer install --no-dev` in the module directory.

Upgrade an existing installation in place, replacing the module files and `vendor` directory, then apply the pending HumDAV database migrations through HumHub's updater. Version 1.6.0 retains settings and existing CardDAV tokens. The migration deletes obsolete iCal and other non-DAV tokens and removes their purpose field. HumDAV's own iCal and CalDAV services have been removed; use the Calendar module for calendars. CalDAV clients using the old HumDAV endpoints must switch to the native service or download a new Apple configuration profile.

Enable HumDAV in the module administration, configure which users may access it, then open **HumDAV** in the top menu to create a device token. Configure the menu location in the module settings if the link is hidden.

Use your HumHub username and a device token as the CardDAV client credentials. Authentication with the HumHub password is optional and disabled by default.

Apple profile downloads include CardDAV in every configuration. When Calendar is enabled, the **Include CalDAV configuration** checkbox is selected by default. The CalDAV account uses the Calendar module's own URL and credentials; clear the checkbox for a CardDAV-only profile.

## Release ZIP

The **Release ZIP** GitHub Action runs when a tag starting with `v` is pushed. The tag must match the version in `module.json`, for example `v1.6.0` for version `1.6.0`.

Commit and push the module changes, including `.github/workflows/release.yml`, `.gitattributes` and the complete `vendor/` directory, before creating the version tag. When dependencies change, run `composer install --no-dev` and commit the updated `composer.lock` and `vendor/` files first. Then run these commands from this module's repository:

```sh
git tag v1.6.0
git push origin v1.6.0
```

The action creates a GitHub release and attaches `humdav-1.6.0.zip`. Download this asset from the release's **Assets** section and upload it directly to the HumHub partner portal. The ZIP contains a single top-level `humdav/` directory, with `module.json` immediately inside it. Use this asset when uploading to the partner portal; GitHub's automatic **Source code** downloads use a different directory name.

The archive contains committed files only. The exclusion rules in `.gitattributes` omit `test/` and `tests/` directories, including dependency tests, PHPUnit configuration and caches, `composer.*`, `.git*` (including the workflow), and macOS metadata. Runtime code, migrations, resources, documentation, dependency licences and the `vendor/` runtime files, including `vendor/composer/`, remain included. Composer does not need to run on the target installation.

Tags that do not match `module.json` fail before a release is created. Versions such as `1.6.0-rc.1` produce a GitHub prerelease. To rerun a release, use **Actions → Release ZIP → Run workflow** and select its version tag. An existing release is retained and its ZIP asset is replaced.

The same package can be built locally after committing the changes, using the version from `module.json`:

```sh
git archive --format=zip --prefix=humdav/ --output=humdav-1.6.0.zip HEAD
```

See [client setup and available collections](docs/README.md) and [running the integration tests](tests/README.md).
