[Home](../README.md) > DAST

# DAST (ZAP scan)

`bin/dast` runs [ZAP](https://www.zaproxy.org/) against a throwaway copy of the app, seeded with surveys it can log in
to, and leaves the reports in `var/dast/`. It needs only Docker. It's run locally, by hand, when wanted: never in CI
(which only checks the script's shell syntax).

```bash
bin/dast            # passive: spider + passive rules
bin/dast --active   # also the active scan (attacks; only ever against this throwaway stack)
bin/dast --smoke    # just the stack's integration checks, then stop
```

It's for regression coverage and evidence: headers and cookies on every page it finds, error pages that leak details,
injection through the requests it has captured, forms without CSRF tokens. It is **not** a pentest. It won't find
business-logic flaws (one survey reaching another's data, skipping workflow steps, the `/cron` header gate, passcode
brute force); those need targeted or manual testing.

## What it does

1. Builds the screenshot image (`docker/screenshots/Dockerfile`) of the current checkout, and starts it in its
   `serve` mode with an empty MySQL 8.0.18 (`compose.dast.yaml`), in the staging environment with debug off.
2. Seeds the database (`bin/console rfs:dev:dast-seed`, `App\Utility\Dev\DastSeeder`): one passcode user per survey
   type, each with a part-completed survey.
3. Runs ZAP once per **section**, each in a fresh ZAP process, in this order:

   | Section | What | Logged in as |
   |---|---|---|
   | `public` | the frontend's public pages, including RoRo's login pages | nobody |
   | `domestic` | `/domestic-survey` | `90000001` (domestic survey: days with stops and with a summary) |
   | `international` | `/international-survey` | `90000002` (a vehicle with a trip and its actions) |
   | `pre-enquiry` | `/pre-enquiry` | `90000003` (a pre-enquiry with a response) |
   | `admin` | the admin site | `dast-admin@example.com` (manager role), faked |

4. Tears everything down, keeping the output.

## What isn't scanned

- **The survey forms' POST handling.** The spider never submits POST forms: doing so changes survey state at random
  (for example, answering that the vehicle was sold deletes the survey's days), so the scan would destroy its own
  data. The forms are still checked as rendered (e.g. for CSRF tokens), and a few active rules send POST probes of
  their own (PHP-CGI and similar remote-code-execution checks, with non-form bodies), but the active scan has no
  captured form submissions to attack.
- **DOM-based XSS** (active rule 40026) is switched off. It drives a real browser that submits the pages' forms, with
  valid CSRF tokens, which deleted and changed the seeded surveys mid-scan (found from the access logs). The app
  renders on the server with little JavaScript.
- **RoRo's logged-in pages.** RoRo logs in with a single-use emailed link, which ZAP can't repeat.
- **Access between users.** Each section has one user; nothing tests whether one user can reach another's data.

So a clean report means "nothing found on what was covered", not that these areas are fine.

## How a scan proves it wasn't hollow

A scan that quietly ran logged out, found nothing, or ran against destroyed data would produce a clean-looking
report. Each section has its own checks, and any failure makes `bin/dast` exit non-zero (with the reports still
written):

- **Discovery:** the spider must find the section's deep pages itself (the *sentinels*: a day with stops, a trip, a
  pre-enquiry question page, the admin view of a survey...). Nothing else requests them before the spider (the login
  check polls the section's summary page), so their presence proves the spider got there. It must also find at least
  a minimum number of URLs (`spider_floor` in `bin/dast`), though that count includes links it saw but didn't follow
  (header and footer links, for example), so the sentinels are the stronger evidence.
- **Live checks:** after the spider (and after the active scan), fresh requests as the section's user must return a
  page showing the seeded data (fixed marker text, `DastSeeder::MARKERS`). These are leaf pages (the reorder-stops
  page, a summary question page, a trip's edit page, the pre-enquiry company-name page, the admin reveal-passcode
  page), not the sentinels: ZAP's URL test resolves a URL that is also a folder in its sites tree (`day-1`, once
  `day-1/stop-1/...` is known) to the folder, not to the fresh response.
- **Attack traffic** (`--active`, survey sections): ZAP's Authentication Statistics add-on must count at least a
  minimum number of the active scanner's own responses as logged in (`ascan_floor` in `bin/dast`). That floor is the
  guard: a scan whose attack requests ran logged out fell to 12 against a floor of 670. It's minimum coverage, not
  proof that every attack request was logged in. There are also limits on responses counted as logged out and on
  failed logins, but they're only backstops: with this login check, ZAP has never counted an attack response as
  logged out, even when the attack traffic was.
- **Finished analysis:** the passive scanner's queue is always drained before the reports (no time limit), and each
  section has a hard deadline instead, so a hung section fails rather than reporting early.

Each section runs in its own ZAP process because ZAP's plan statistics are counted from the start of a process: in a
shared one, an earlier section's numbers could satisfy a later section's checks.

## How the logins work

- **Survey sections:** ZAP's form login on `/login`, polling the section's summary page every 60 requests to check
  it's still logged in. Logged out, the summary redirects to `/login`, and the check matches that `Location` header:
  ZAP treats a poll that matches neither indicator as still logged in. The login body carries an extra, ignored
  `passcode_login_token` parameter: ZAP names the form's CSRF token after the input's `id`, and replaces that
  parameter's placeholder value wherever it appears, so the real token field gets the fresh token too.
- **Admin:** faked. Outside App Engine, `App\Security\GoogleIap\IapAuthenticator` logs in whoever the
  `TEST_GOOGLE_IAP_AUTH_USER` server variable names (the functional tests use the same mechanism), and the DAST
  Caddyfile (`docker/dast/Caddyfile`) sets it for the admin host only.

## Isolation

Everything runs on one Docker network that is `internal`: there's no route out, so neither ZAP nor the app can reach
Notify, GCP or the internet during a scan (no message worker runs either). No ports are published, but the Docker host
can still reach the containers directly: the host is trusted. Image builds aren't affected; they don't use that
network. `--smoke` checks the isolation from both containers.

## Output

`bin/dast` prints the output directory as it starts, then each section's ZAP job lines as they happen (the full ZAP
output is in the section's `zap-console.log`). Everything goes to `var/dast/<YYYYMMDD-HHMMSS>-<pid>/`:

| File | What |
|---|---|
| `<section>/report.html` (+ `report/`), `report.json` | ZAP's findings for the section |
| `<section>/urls.txt`, `urls.txt` | every URL the section covered, and all of them |
| `<section>/traffic.har` | the section's requests and responses, with HTTP methods (passive runs only: active ones get huge) |
| `<section>/zap-plan.yaml` | the plan as run (rendered from `docker/dast/plans/`) |
| `<section>/zap-console.log`, `zap.log` | ZAP's output and its own log |
| `autocheck.log` | ZAP's validation of every plan before the scan |
| `access-frontend.log`, `access-admin.log` | every request the app received (method, URI, status, headers; active-scan requests carry the sending rule's id in `X-ZAP-Scan-ID`) |
| `app-console.log`, `frankenphp.log`, `app-var-log/` | the app's logs |
| `teardown.log` | Compose's teardown |

## Exit status

- `0`: every section ran and passed its checks, whatever ZAP found.
- `2`: bad options (including an empty or unknown `DAST_SECTIONS`), before anything starts.
- non-zero: the first failing section's status (ZAP's `1` for errors, `2` for warnings such as an unexpected response
  code, `124`/`137` for a section that hit its deadline), `130`/`143` if interrupted, or `1` for anything else (a
  plan that failed ZAP's check, a container left behind). If a section's ZAP container can't be confirmed gone, the
  run stops there, without starting the next section, so two scans never overlap. The log names each section's result.

## Options

| Variable | Default | |
|---|---|---|
| `DAST_SPIDER_MINS` | 10 | each section's spider limit |
| `DAST_ACTIVE_MINS` | 30 | each section's active scan limit (`--active`) |
| `DAST_CONTEXT_TIMEOUT_MINS` | 3 × their sum + 15 | each section's hard deadline |
| `DAST_SECTIONS` | all five | a subset, e.g. `DAST_SECTIONS=domestic` while investigating. Always run in the fixed order, once each; an empty list or an unknown name stops `bin/dast` before it starts anything (exit 2) |
| `DAST_WRONG_PASSWORD=<section>` | | test hook: a wrong password for that section's user (its checks must fail) |
| `DAST_CRIPPLE_SPIDER=<section>` | | test hook: that section's spider limited to depth 1. Shown to fail international's discovery checks; it can't cripple pre-enquiry (all its pages are one link from the summary), and domestic's sentinels are one link deep too |
| `DAST_ASCAN_LOGGED_OUT=<survey section>` | | test hook, with `--active`: that section's active scan runs without its user, and `bin/dast` ends every app session as it starts (without that, the recorded requests' session cookie keeps it logged in). Its logged-in floor must fail |
| `DAST_STALL_SECTION=<section>` | | test hook: that section's ZAP replaced by a process that hangs and ignores TERM (its deadline must stop it) |

## Maintenance

- **ZAP version:** pinned in `docker/dast/zap.Dockerfile` (with the Authentication Statistics add-on, pinned by
  checksum from ZAP's add-on index). Bump by hand, then run `bin/dast --smoke` and a full `bin/dast --active`.
- **Feature flags:** like the screenshots, the stack turns on every release flag in `App\Features`
  (`compose.dast.yaml`).
- **Calibration:** the spider and active-scan floors in `bin/dast` were set from good runs (the observed counts are
  in comments there). If the app grows or
  shrinks a lot, re-read the counts from a good run's `zap-console.log` and adjust.

## Follow-ups

- POST-form coverage: a sacrificial second user per survey type, crawled with form submission on after the safe pass.
- A combined report across sections.
- RoRo's logged-in pages.
- Access-control checks with a second user per type.
