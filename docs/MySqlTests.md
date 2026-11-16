[Home](../README.md) > MySQL tests

# MySQL tests

The main test suite runs on SQLite. Production runs MySQL (Cloud SQL, MySQL 8.0.18), with its schema built by
migrations and then `app:views:apply`. So a separate, small suite in `tests/MySQL/` covers what SQLite can't:

- the migrations and the views/functions in `sql/` (see [Views](Views.md)) build on a fresh database
- the views return the right data
- SQL that only runs on MySQL
- settings that make MySQL behave differently from SQLite

It isn't a second run of the whole suite: only code that depends on MySQL itself belongs here.

## Running it

```bash
bin/test-mysql                     # the whole mysql suite
bin/test-mysql --filter ViewsTest  # extra arguments go to phpunit
```

It needs only Docker (with the Compose plugin). Each run:

1. builds the PHP 8.3 image from `docker/test-php/` (cached after the first time)
2. starts a disposable MySQL 8.0.18 (`compose.mysql-tests.yaml`) with its data in memory
3. runs `composer install` into the checkout's `vendor/`, so it also brings `vendor/` in line with `composer.lock`
4. drops and rebuilds the database as production is built: migrations, `app:views:apply`, `doctrine:schema:validate`,
   `messenger:setup`
5. runs `phpunit --testsuite mysql`, writing JUnit XML to `build/test-results/mysql.xml`
6. removes the containers, network and image tag (Docker's build cache stays)

It exits with phpunit's exit code. Each run gets its own Compose project and image tag, so runs from separate worktrees
can overlap. The CI job is the same command.

## What it covers

| Area | Tests (`tests/MySQL/`) |
|---|---|
| Building the schema as production does | the bootstrap itself: migrations, `app:views:apply`, schema validation |
| Server settings match production | `ServerSettingsTest` |
| Views: every view has rows; derived columns are right | `ViewsTest` (data from `tests/DataFixtures/MySQL/ViewCoverageFixtures.php`), `DomesticWeekNumbersTest` |
| SQL functions match the PHP they mirror | `StoredFunctionsTest` |
| Foreign keys enforced as in production (the HGV-zero crash) | `DataConsistencyListenerCrashRegressionTest` |
| MySQL-only SQL | `NotificationInterceptionDvlaImportTest` (DELETE … JOIN), `ReportsTest` (WEEK/YEAR/MONTH, under `ONLY_FULL_GROUP_BY`), `OnHireStatsProviderTest` (IF/CAST), `UnapprovalsReportTest` (GROUP_CONCAT) |
| Behaviour that differs from SQLite | `OperatorGroupMatchingTest` (case- and accent-insensitive collation) |
| Existing SQLite tests re-run on MySQL | the reminder selection tests, `OperatorGroupRepositoryTest`, `AuditLogStateQueriesTest` (JSON), the SMS allowance/cap tests (thin subclasses) |
| Database-backed infrastructure | `InfrastructureTest` (Messenger doctrine transport, lock store; single process, not concurrency) |

## How close to production is it?

Google doesn't publish its `8.0.18-google` build, so the suite runs stock `mysql:8.0.18` configured to match. On
2026-11-16, production's `SHOW GLOBAL VARIABLES` was compared with stock 8.0.18 for the 44 variables that affect query
results or schema behaviour (`sql_mode`, collations, time zone, week format, `group_concat_max_len` and so on). 40
matched. The other four are set in `compose.mysql-tests.yaml`:

| Variable | Production / stock | Why it matters |
|---|---|---|
| `log_bin_trust_function_creators` | ON / OFF | Without it, `app:views:apply` can't create the functions as a non-root user (error 1419) |
| `max_allowed_packet` | 32M / 64M | Production is stricter |
| `local_infile` | ON / OFF | For parity |
| `default_authentication_plugin` | `mysql_native_password` / `caching_sha2_password` | For parity |

The app's own connection uses `utf8mb4_general_ci`, not the server's `utf8mb4_0900_ai_ci`. That's because PHP's mysqlnd
negotiates it, in production too (measured through `bin/console dbal:run-sql`). A `mysql` command-line session shows
the server default instead.

`tests/MySQL/ServerSettingsTest` asserts all of these, so if the stack drifts from production, the suite fails. The
deploy to the test environment, which runs on real Cloud SQL, remains the only fully faithful check.

## Safety

The bootstrap drops the database it's given, so `tests/Support/MySqlTestDatabaseGuard` checks `DATABASE_URL` before
anything touches it:

- **One URL.** If `$_SERVER`, `$_ENV` and the process environment disagree, the bootstrap refuses to run. Otherwise
  it uses the checked value for every console command and test kernel.
- **Plain URLs only.** Parameters that could route the connection elsewhere (`driverClass`, `wrapperClass`,
  `unix_socket`...) are refused, as are `port` or `charset` values that aren't plain (they're joined into PDO's
  `key=value;...` DSN, so a `;` could override the host). So is anything that's neither SQLite nor MySQL.
- **Any MySQL URL is guarded,** whatever its scheme (`mysql://`, `mysql2://`, `pdo-mysql://`, `mysqli://`). It's
  refused unless all of these hold:
  - the host is `rfs-test-mysql`, the Compose service, never the dev `mysql` container
  - the database is `rfstest`. Deliberately no `_` or `%`: both are wildcards in MySQL database grants.
  - the user's grants reach no other database

## Adding tests

- **Put tests in `tests/MySQL/`.** They're excluded from the default suite.
- **Stay below the HTTP layer:** repositories, helpers, SQL. The image has no Node or built assets.
- **New views:** add qualifying data to `tests/DataFixtures/MySQL/ViewCoverageFixtures.php`, or `ViewsTest` fails.
  Assert the columns the view derives in SQL.

## Foreign keys in the SQLite suite

SQLite only enforces foreign keys when asked, so the test environment registers
`tests/Support/SqliteForeignKeysMiddleware` to turn them on. Without it, deletes and inserts that break a foreign key
pass in tests and fail in production.
