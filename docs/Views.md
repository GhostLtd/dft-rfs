[Home](../README.md) > Database views and functions

# Database views and functions

Views (and SQL functions) live as plain `.sql` files under [`sql/`](../sql). They are applied to the database by the `app:views:apply` console command, **not** by Doctrine migrations.

## Why this is a separate system

Doctrine migrations are a delta log. Each migration encodes an incremental change — add a column, rename an index, backfill data — and the migrations apply in filename/version order on top of whatever state the previous migrations left behind. That model fits incremental DDL on tables very well. It fits views and functions badly, because a view's "correct current state" is its **whole body**, not a delta — so each view-touching migration wholesale-replaces the view with a `CREATE OR REPLACE VIEW` statement.

This turns into a real regression hazard as soon as more than one feature branch is in flight. Suppose branch A adds a column to `view_foo`. Branch B, cut before A was merged, adds a different column to the same view — its migration carries a full `CREATE OR REPLACE VIEW view_foo …` using the view body as it looked when B was cut, i.e. without A's column. If A is merged first and B second, B's migration silently reverts A's column on the next migrate run.

That is not a hypothetical — it bit us in February 2026. The HGV/LGV split branch (authored 2025-11-27) was only committed to master on 2026-02-26, one day after a `SubmissionDate` migration (`Version20260225120000`) was written against the pre-split view body. Once both commits landed on master, Doctrine's filename-order migration run reverted `view_international_firms` / `view_export_international_survey` from the HGV/LGV-split columns back to the single pre-split column — and the revert referenced `sr.annual_international_journey_count`, a column a December 2025 migration had already renamed, so fresh-DB migrates failed outright.

Moving views and functions to `sql/` eliminates this class of bug: there is exactly one file per object, the latest file on the branch wins unconditionally, and merge conflicts surface as real merge conflicts on that one file — not as silent DDL reverts between unrelated migrations.

## Layout

```
sql/
├── views/                                 # one .sql file per view
│   ├── view_countries.sql
│   ├── view_international_firms.sql
│   └── ...
└── functions/                             # one .sql file per stored function
    └── irhs_empty_uuid.sql
```

- **Filename = object name.** `sql/views/view_countries.sql` must contain `CREATE OR REPLACE VIEW view_countries …`. `sql/functions/irhs_empty_uuid.sql` must contain `CREATE FUNCTION irhs_empty_uuid(…)`. The command uses the filename to compute the `DROP FUNCTION IF EXISTS <name>` it emits before each function.
- **Views** are applied verbatim as a single `CREATE OR REPLACE VIEW …` statement — idempotent in MySQL, no DROP needed.
- **Functions** are applied as two statements: `DROP FUNCTION IF EXISTS <name>` followed by the file body (which should contain a single `CREATE FUNCTION <name> …`). The DROP is emitted by the command; you do not write it in the file.

## Declaring dependencies

When one view `FROM`s or `JOIN`s another, declare the dependency at the top of the file with a `-- @depends` comment:

```sql
-- @depends view_international_firms
-- @depends view_international_trips
-- @depends view_international_consignments

CREATE OR REPLACE VIEW view_export_international_survey AS (
    SELECT f.*, t.*, c.* FROM view_international_firms AS f
    LEFT JOIN view_international_trips AS t ON ...
    LEFT JOIN view_international_consignments AS c ON ...
);
```

Rules:
- One `-- @depends <name>` per line, at the top of the file (before the `CREATE` statement, blank lines and other `--` comments are allowed between them).
- Only declare real `FROM`/`JOIN` references to other views/functions in `sql/`. Table references don't need declaring.
- Missing `-- @depends` → the apply order may be wrong and MySQL will error at `CREATE` time (unknown column / unknown view).
- The command topologically sorts on load. Cycles produce a clear `Dependency cycle: view_a -> view_b -> view_a` error.

## Adding or editing a view

1. Create or edit the file in `sql/views/<name>.sql` (or `sql/functions/<name>.sql`).
2. If the body references another view/function in `sql/`, add the appropriate `-- @depends` line(s).
3. Run `bin/console app:views:apply` locally to apply against your dev DB.
4. Commit the `.sql` file.

**Do not add `CREATE VIEW`, `CREATE OR REPLACE VIEW`, `CREATE FUNCTION`, or `CREATE PROCEDURE` to Doctrine migrations.** If a column rename/remove means a view body needs updating, update the corresponding file in `sql/views/` in the same branch as the migration.

## Running the command

```
bin/console app:views:apply               # apply every view and function
bin/console app:views:apply --dry-run     # print the order and SQL without executing
bin/console app:views:apply --only=view_x # apply view_x and its dependencies
bin/console app:views:apply --only=view_x --no-deps   # apply view_x alone
bin/console app:views:apply -v            # echo each statement as it runs
```

The command is MySQL/MariaDB-specific and gracefully skips on other platforms (e.g. the SQLite test database), emitting a note and exiting 0. So the main (SQLite) test suite never creates the views: they're tested by the MySQL test suite instead.

## Deploy / test integration

- **Tests:** the [MySQL test suite](MySqlTests.md) (`bin/test-mysql`) builds a fresh MySQL 8.0.18 database the way production is built — `doctrine:migrations:migrate`, then `app:views:apply` — and `tests/MySQL/ViewsTest` checks that every view returns rows and that the columns they derive are right. A view that no longer builds after a schema change fails the run. Add qualifying data to `tests/DataFixtures/MySQL/ViewCoverageFixtures.php` for any new view.
- **Deploys / prod / dev:** `app:views:apply` needs to run after `doctrine:migrations:migrate`, wherever that happens in the deploy runbook. It is not wired into `cloudbuild.yaml` because `cloudbuild.yaml` does not run migrations either (it deploys the app and the `post-install` hook only checks `d:m:status`). Whoever runs `d:m:migrate` must also run `app:views:apply` immediately after.

## The rule, restated

No new migration may contain `CREATE VIEW`, `CREATE OR REPLACE VIEW`, `CREATE FUNCTION`, or `CREATE PROCEDURE`. PR reviewers should flag this. All such definitions live in `sql/`, and they are applied by `app:views:apply`.
