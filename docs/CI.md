[Home](../README.md) > CI

# CI

GitHub Actions runs the workflows in `.github/workflows/`. Every job runs a command you can also run locally, so a red
job can be reproduced without GitHub.

No job needs a secret. The `notify-api` test group stays excluded, and nothing talks to GCP.

## Tests (`ci.yml`)

Runs on every push and pull request, and can be started by hand from the Actions tab.

| Job | What it runs | Run it locally | Artifacts |
|---|---|---|---|
| `tests` | the default (SQLite) suite, after `yarn build`; Chrome for `JavaScriptBehaviourTest` comes with the runner | `yarn build && vendor/bin/phpunit` | `test-results` (JUnit); `panther-error-screenshots` on failure |
| `static` | `composer validate`, `lint:container`, `lint:yaml`, `lint:twig`, PHPStan, shell syntax of the `bin/` and `docker/` scripts | the same commands, with `APP_ENV=test` | (none) |
| `mysql-tests` | the MySQL suite on a disposable MySQL 8.0.18 (see [MySQL tests](MySqlTests.md)) | `bin/test-mysql` | `mysql-test-results` (JUnit) |

## Dependency audits (`audit.yml`)

Runs when `composer.lock`, `yarn.lock`, `screenshots/yarn.lock` or the audit workflow and its summary script change,
every Monday morning (new advisories turn up without any change here), and by hand. It's a separate workflow so that a newly published advisory doesn't turn the
test results red.

| Job | What it runs | Run it locally | Fails on |
|---|---|---|---|
| `php-audit` | `composer audit` on `composer.lock` | `composer audit --locked --abandoned=report` | any advisory. Abandoned packages are listed but don't fail it. |
| `js-audit` | `yarn npm audit --all --recursive` for the asset build (`/`) and the screenshot tool (`screenshots/`) | `yarn npm audit --all --recursive` (in each directory) | only an audit that didn't run. Advisories are report-only. |

The yarn audit includes devDependencies on purpose: almost everything in `package.json` is one, including libraries
that end up in the shipped bundles, so `--environment production` would hide them. Its advisories are listed in the
job summary (`.github/scripts/yarn-audit-summary.mjs`) until the current backlog is triaged. To make it blocking,
have that script exit 1 when an advisory is `high` or `critical`.

## Screenshots (`screenshots.yml`)

Runs by hand (Actions tab, "Run workflow", choosing the branch, the survey type and `APP_FEATURES`), and when the
`screenshots` label is added to a pull request. Pushing more commits to a labelled PR doesn't re-run it: remove and
re-add the label, or run it by hand on the PR's branch. It runs `bin/screenshots` (see [Screenshots](Screenshots.md))
and uploads `screenshots/output/` as the artifact `screenshots-<run number>`, kept for 14 days. The upload happens even
when a survey type fails, so `error.png` is there to look at.

Adding any other label to a PR still logs a skipped Screenshots run: GitHub can't filter `labeled` events by label name
before starting a run.

## Keeping the workflows current

Actions are pinned to a full commit SHA, with the version in a comment, and Docker images to exact tags. Nothing
updates them automatically (there's deliberately no Dependabot), so bump them by hand now and then: the actions in
`.github/workflows/`, and the `FROM` and `COPY --from` images in `docker/test-php/` and `docker/screenshots/`. Keep PHP
on 8.3 (production's runtime) and Node on 22. Vulnerable Composer and Yarn packages are reported by the dependency
audits above.

To check a workflow change:

```bash
docker run --rm -v "$PWD:/repo" -w /repo rhysd/actionlint:1.7.12 -color
```
