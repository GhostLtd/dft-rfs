# Gov.uk / DfT - Road Freight Survey

## Requirements
- PHP 8.3+
- Mysql / MariaDB
- Node 14 (to build CSS/JS assets)
- Gov.uk Notify API key

## Running the tests
```bash
yarn build                 # first, and again after changing anything under assets/
vendor/bin/phpunit
```

- Tests that call the real GOV.UK Notify API are in the `notify-api` group, which is excluded by default. They need a
  real (test) key in `ALPHAGOV_NOTIFY_API_KEY` (e.g. in `.env.test.local`); run them with `vendor/bin/phpunit --group notify-api`.

- The functional tests run in-process; only `tests/NewFunctional/JavaScriptBehaviourTest.php` drives a real browser (Panther),
  so it needs Chrome plus a matching chromedriver in `drivers/` (`vendor/bin/bdi detect drivers`).
- `JavaScriptBehaviourTest` exercises the compiled bundles in `public/build`, so a stale or missing build fails it.
- Don't run two test runs at once: they share the SQLite test database and corrupt each other.
- See [BrowserKit-first functional tests](docs/ADRs/browserkit-first-functional-tests/index.md) for why, and what to watch out for.

## Relation documents / config
- [Change log](docs/ChangeLog.md)
- [Env files](docs/Env.md)
- [Reminders](docs/Reminders.md)
- [RoRo](docs/RoRo.md)
- [Database views and functions](docs/Views.md)
- [Symfony upgrade notes](UPGRADE-NOTES.md)