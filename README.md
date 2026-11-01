# HumDAV

Read-only CardDAV contacts for HumHub, with optional native Calendar CalDAV configuration for Apple devices.

See the [feature overview and data notice](docs/README.md), [installation and upgrade instructions](docs/INSTALLATION.md), and [client manual](docs/MANUAL.md). Optional personal data exports are disabled by default and always respect HumHub profile permissions and field visibility.

## Release ZIP

The **Release ZIP** GitHub Action runs when a tag starting with `v` is pushed. The tag must match the version in `module.json`, for example `v1.6.0` for version `1.6.0`.

Commit and push the module changes, including `.github/workflows/release.yml`, `.gitattributes` and the complete `vendor/` directory, before creating the version tag. When dependencies change, run `composer install --no-dev` and commit the updated `composer.lock` and `vendor/` files first. Then run these commands from this module's repository:

```sh
git tag v1.6.0
git push origin v1.6.0
```

The action creates a GitHub release and attaches `humdav-1.6.0.zip`. Download this asset from the release's **Assets** section and upload it directly to the HumHub partner portal. The ZIP contains a single top-level `humdav/` directory, with `module.json` immediately inside it. Use this asset when uploading to the partner portal; GitHub's automatic **Source code** downloads use a different directory name.

The archive contains committed files only. The exclusion rules in `.gitattributes` omit `test/` and `tests/` directories, including dependency tests, PHPUnit configuration and caches, `.git*` (including the workflow), and macOS metadata. The archive also omits `vendor/sabre/dav/bin/`, its `vendor/bin/` proxies, and the PHP-CS-Fixer configuration. Runtime code, migrations, resources, documentation, dependency licences, `composer.json`, `composer.lock`, and the `vendor/` runtime files, including `vendor/composer/`, remain included so dependencies can be audited. Composer does not need to run on the target installation.

Tags that do not match `module.json` fail before a release is created. Versions such as `1.6.0-rc.1` produce a GitHub prerelease. To rerun a release, use **Actions → Release ZIP → Run workflow** and select its version tag. An existing release is retained and its ZIP asset is replaced.

The same package can be built locally after committing the changes, using the version from `module.json`:

```sh
git archive --format=zip --prefix=humdav/ --output=humdav-1.6.0.zip HEAD
```

See [client setup and available collections](docs/MANUAL.md) and [running the integration tests](tests/README.md).
