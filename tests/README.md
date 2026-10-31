# HumDAV tests

The tests cover HumHub 1.19 / PHP 8.2+, including token ownership and revocation, settings, vCards, optional native CalDAV credentials in Apple profiles, and migration of legacy tokens to CardDAV only.

## PHPUnit integration suite

Use a disposable copy of an installed HumHub database with HumDAV enabled. The bootstrap accepts database names starting with `humdav_test_` and applies pending HumDAV migrations before the suite starts. Tests roll back their fixtures or use connection-local temporary tables. Optional Calendar installation and migrations also happen in the disposable database.

For the Docker development environment, create the copy with:

```sh
docker compose exec -T db sh -c 'mariadb -uroot -p"$MYSQL_ROOT_PASSWORD" -e "CREATE DATABASE humdav_test_suite CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"'
docker compose exec -T db sh -c 'mariadb-dump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction humhub' \
  | docker compose exec -T db sh -c 'mariadb -uroot -p"$MYSQL_ROOT_PASSWORD" humdav_test_suite'
docker compose exec -T \
  -e 'HUMDAV_TEST_DB_DSN=mysql:host=db;dbname=humdav_test_suite' \
  app php /opt/humhub/protected/vendor/bin/phpunit \
  -c /data/modules/humdav/phpunit.xml.dist
```

To include the Calendar tests, pass `-e HUMDAV_TEST_CALENDAR_PATH=/path/to/calendar` to the same command. Use an unpacked Calendar version compatible with HumHub 1.19 and install its Composer dependencies first. This enables Calendar in the database copy only.

Other layouts can set `HUMDAV_TEST_PROTECTED_PATH` and `HUMDAV_TEST_CONFIG_PATH`. Database credentials are inherited from the HumHub environment. PHPUnit 9.6 or later can run the suite.

Remove the disposable database after testing:

```sh
docker compose exec -T db sh -c 'mariadb -uroot -p"$MYSQL_ROOT_PASSWORD" -e "DROP DATABASE humdav_test_suite"'
```

## HTTP smoke suite

Run the HTTP suite against an enabled development installation after applying the module migrations. It signs in, creates temporary tokens, exercises the forms, discovery, CardDAV PROPFIND/REPORT/GET, write protection, token revocation and Apple downloads with and without CalDAV, then revokes the CardDAV tokens it created. It also checks that the obsolete token-purpose fields and iCal service are gone. When Calendar is enabled, it verifies that the generated native CalDAV credentials authenticate successfully. It uses Python's standard library.

```sh
HUMDAV_TEST_URL=http://localhost:8100 \
HUMDAV_TEST_USERNAME=admin \
HUMDAV_TEST_PASSWORD='your-development-password' \
python3 modules/humdav/tests/http_smoke.py
```
