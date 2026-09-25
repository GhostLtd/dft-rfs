[Home](../README.md) > [Reminders](Reminders.md) > Rehearsal

# Rehearsing reminders before a release

A step-by-step check that every reminder (invites, not-started reminders, in-progress email + SMS, IRHS,
pre-enquiry, RoRo) is selected, sent to GOV.UK Notify and logged correctly. There are three parts:

- **A. Local rehearsal** — the main test. It runs on your machine against a scratch database, with a Notify
  *test* key, so nothing is delivered. Takes about 15 minutes, plus an optional check 4 hours later.
- **B. One real SMS** — proves Notify really delivers our SMS. Takes 2 minutes.
- **C. GCP smoke test** — after deploying to GCP dev, checks the crons and settings are wired
  up. Takes 10 minutes.

Before a prod go-live there's also **D. Prod go-live checks**.

## A. Local rehearsal

Do this on a **weekday** (in-progress reminders never run at weekends), and not on the last day of a month
(RoRo reminders look at the previous month). Finish on the day you start.

### A1. Configure the local environment

You need:

- the Notify **test** API key for the service (Notify → API integration → API keys; "Test" type);
- the real template ids in `src/Utility/AlphagovNotify/Reference.php`;
- your usual local development environment and MySQL running.

Run the commands below from the project root. Configure these settings in your local environment (for example,
in `.env.local`), keeping a note of any existing values so you can restore them afterwards:

| setting | rehearsal value |
|---|---|
| `APP_ENV` | `dev` |
| `DATABASE_URL` | Your normal local MySQL connection URL, but with database name **`rfs_rehearsal`** |
| `ALPHAGOV_NOTIFY_API_KEY` | The Notify **test** key |
| `APP_IN_PROGRESS_REMINDERS_FROM` | `2026-01-01` (or another valid date at least 10 days before today) |
| `APP_DISABLE_REMINDERS` | Empty, so all reminder families are enabled |

Use only a disposable local database: the rehearsal drops and recreates it. Keep the **test** key configured
throughout part A; live keys are used only as one-command overrides in parts B and D.

Before anything drops a database, check the effective connection:

    bin/console debug:container --env-var=DATABASE_URL

Confirm both the local server and the database name `rfs_rehearsal` (before any `?` query parameters). If either
is wrong, stop and correct the settings.

If database creation fails because your local MySQL user lacks permission, have a database administrator create
`rfs_rehearsal` and grant that user access before continuing.

### A2. Create the scratch database

Do this before every rehearsal, even if `rfs_rehearsal` already exists: the seed command only seeds a freshly
created database, and refuses one that already holds a seed or any other surveys, notifications or SMS counts.

    bin/console doctrine:database:drop --force --if-exists
    bin/console doctrine:database:create
    bin/console doctrine:migrations:migrate -n
    bin/console app:views:apply
    bin/console messenger:setup-transports
    bin/console doctrine:query:sql 'SELECT DATABASE()'
    bin/console app:dev:seed-reminder-scenarios --check-baseline

The query must print `rfs_rehearsal`, and the last command must say the database is ready for seeding. The
migrations insert some reference data (ports, routes, countries, a RoRo operator group or two); that's expected
and allowed.

### A3. Seed the scenarios

    bin/console app:dev:seed-reminder-scenarios

Type `rfs_rehearsal` when asked. It prints a table of every scenario: its survey, and what should happen to it.
Keep it for reference. If it refuses because the database already holds a seed or other data, go back to A2.

### A4. Check the Notify templates

    bin/console app:dev:check-notify-templates

Every template should be ✅. A ❌ usually means a template uses a placeholder we don't send; fix the template in
Notify, or our personalisation, before going further.

### A5. Preview what will be sent

    bin/console app:cron:reminders:process --dry-run
    bin/console app:cron:reminders:process-in-progress --dry-run

Compare with the table from A3: every scenario marked to send should appear, with the expected channels.
Scenarios marked "nothing" should either be absent or listed as held back. `ip:r1-no-contact` is a special
case of this: it appears as in-progress-reminder-1 due, but with every channel skipped (email: no email
recipients; sms: no telephone).

### A6. Send for real (to Notify's test key)

    bin/console app:cron:reminders:process
    bin/console app:cron:reminders:process-in-progress
    bin/console messenger:consume async_notify async_notify_high_prio --time-limit=60

The last command sends the queued messages to Notify and records the responses. It stops after 60 seconds.

### A7. Check the results

    bin/console app:dev:seed-reminder-scenarios --report

Every scenario should be ✅. The three **invite** scenarios show as *pending*: invites are deliberately
delayed 4 hours. Everything else should be *accepted*. A seed more than 24 hours old can't be reported on; start
again from A2.

Optional: if you run the admin site locally (against `rfs_rehearsal`), open a couple of the surveys from the
table. The "Invitation/Reminder details" tab should show the same notifications. "Send reminder" on the
`ip:r1-sms` survey should say an automated reminder was sent too recently.

### A8. (Optional, 4+ hours after A3) Check the invites

    bin/console messenger:consume async_notify async_notify_high_prio --time-limit=60
    bin/console app:dev:seed-reminder-scenarios --report

The invites should now be *accepted* too.

### A9. Tidy up

With the rehearsal settings still active, recheck the connection as in A1, then drop the scratch database:

    bin/console doctrine:database:drop --force

Restore any local settings you changed in A1. The next rehearsal starts by configuring those settings again,
then recreating the scratch database as in A2.

## B. One real SMS

This needs the Notify **team and guest list** API key, and a mobile number belonging to a team member of the
Notify service (or on its guest list).

Override the key for this command only; don't replace the rehearsal's test key in your local settings:

    ALPHAGOV_NOTIFY_API_KEY='<team key>' bin/console app:dev:send-test-sms 07XXXXXXXXX

Confirm when asked. Within a minute the text should arrive on that phone, and appear on the Notify dashboard.
It uses the in-progress reminder 1 SMS template, with sample details.

## C. GCP smoke test (GCP dev)

The local rehearsal proves the code. This proves the **deployment**: that App Engine runs both reminder crons,
that the new `_APP_IN_PROGRESS_REMINDERS_FROM` setting reaches the app, and that the migrations ran.

GCP dev's `_ALPHAGOV_NOTIFY_API_KEY` is a Notify **test** key, so nothing it sends is ever delivered, and
"Run now" is harmless.

1. Deploy the release to GCP dev, with `_APP_IN_PROGRESS_REMINDERS_FROM` set to the date you plan to use, and
   `_APP_DISABLE_REMINDERS` as normal. Do this on a **weekday**.
2. In the GCP console → App Engine → Cron jobs, check both reminder jobs are listed: "Reminder processor" at
   06:00, and "CSRGT in-progress reminder processor" at 09:00.
3. Click **Run now** on each (or wait for their scheduled runs). In Logs Explorer, expect `Notifications:`
   lines per family and type, e.g. `reminder-1 for csrgt: N due` and
   `in-progress-reminder-1 for csrgt-in-progress: N due`. If you see `APP_IN_PROGRESS_REMINDERS_FROM unset or
   invalid`, the substitution didn't reach the app.
4. Check the migrations ran: the deploy log, or `bin/console doctrine:migrations:status` via the Cloud SQL proxy.

## D. Prod go-live checks

1. **Templates against the prod Notify service:** configure, create and seed the scratch database as in A1–A3,
   using the **test** key. Then, against that same seed, run *only* the template check against the **prod** Notify
   service, with a one-off key override on the command itself:

       ALPHAGOV_NOTIFY_API_KEY='<prod key>' bin/console app:dev:check-notify-templates

   > **Keep the rehearsal's configured key as a test key.** Leaving a live (team or prod) key in your local
   > settings would make A6's `bin/console messenger:consume …` really send. The override above applies only to
   > the template check and leaves subsequent commands using the test key.

   Previewing sends nothing. Then tidy up as in A9.
2. **Size the first in-progress run** before setting the date on prod. Connect to the prod database through the
   Cloud SQL proxy, **with a read-only database user**, and with your local checkout at the release being
   deployed. Use the proxy host and port reachable from your local environment. Override the connection for
   this command only, so subsequent commands retain your local database settings:

       DATABASE_URL='mysql://READONLY_USER:PASSWORD@PROXY_HOST:PROXY_PORT/PROD_DATABASE' \
         bin/console app:cron:reminders:process-in-progress --dry-run \
           --from='<planned go-live date>' --disabled= --summary

   Pass `--from` and `--disabled` as above: without them the dry run uses your local `.env` settings, not
   prod's. Check the counts, and the SMS total, look sensible. SMS skipped because several surveys share a
   number show in the summary as `SMS per-number daily cap reached`.
3. Set `_APP_IN_PROGRESS_REMINDERS_FROM` on the prod trigger, and redeploy.

## Commands

### `app:dev:seed-reminder-scenarios`

Seed or report on the reminder rehearsal scenarios.

| option | meaning |
|---|---|
| *(none)* | Seed every scenario into a freshly created database. Refuses if the database already holds a seed or any operational data (recreate it as in A2). Prints the scenario table: name, survey id, registration / company, and what should happen |
| `--report` | Don't seed; report each scenario's outcome |
| `--check-baseline` | Don't seed; read-only: check the database is ready for seeding (every operational table empty; migration reference data allowed). A seeded database fails |
| `--force` | Skip the typed database-name confirmation |

### `app:cron:reminders:process` / `app:cron:reminders:process-in-progress` `--dry-run`

List what each cron would send, without sending or writing anything, using the same eligibility code as a real
run.

| option | meaning |
|---|---|
| `--dry-run` | List what would be sent, without sending or writing anything |
| `--at=<time>` | Evaluate as if run at this time, Europe/London, e.g. `"2026-10-05 09:00"` (default: now) |
| `--from=Y-m-d` | `process-in-progress` only: the effective `APP_IN_PROGRESS_REMINDERS_FROM` (default: the configured value) |
| `--disabled=<list>` | The effective `APP_DISABLE_REMINDERS` (comma-separated; `--disabled=` for none; default: the configured value) |
| `--sms-cap=N` | The effective `APP_SMS_ANNUAL_CAP` |
| `--sms-daily-cap=N` | The effective `APP_SMS_DAILY_CAP_PER_NUMBER` |
| `--limit=N` | Show at most N rows per type (totals and the SMS budget simulation are unaffected) |
| `--summary` | Show counts only |

Per-number counts from earlier the same day are looked up with this checkout's `APP_SECRET`. Against another
environment's database (as in D2), they don't match and read as zero, so the dry run can predict more SMS than the
real run would send. Shared numbers within the run are still counted.

### `app:dev:check-notify-templates`

Preview every Notify template with the personalisation we send (sends nothing; needs a seed).

### `app:dev:send-test-sms <mobile>`

Send ONE real SMS via GOV.UK Notify, to prove SMS delivery (use the team key).

| option | meaning |
|---|---|
| `mobile` (argument) | A UK mobile of a team member of the Notify service (or on its guest list) |
| `--template=<id>` | Notify SMS template id (default: `SMS_DOMESTIC_SURVEY_IN_PROGRESS_REMINDER_1`) |
| `--survey=<id>` | Use the personalisation of this CSRGT survey (id) instead of the sample personalisation |
