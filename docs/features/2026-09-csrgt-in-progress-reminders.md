[Home](../../README.md) > [Reminders](../Reminders.md) > CSRGT in-progress reminders

# CSRGT in-progress reminders, SMS limits and login links

**Status:** implemented.

CSRGT respondents with unfinished surveys receive up to three reminders by email and SMS. This document
describes the schedule, recipients, short login links and SMS limits. For the shared notification system,
see [Reminders.md](../Reminders.md); for release checks, see [RemindersRehearsal.md](../RemindersRehearsal.md).

## Schedule and eligibility

The reminder family is `csrgt-in-progress`, implemented by `DomesticInProgressReminders`. It runs at
09:00 Europe/London on weekdays. The daily 06:00 reminder run excludes this family.

| Reminder | Due when | Notification type |
|---|---|---|
| 1 | Survey period ended at least 7 days ago | `in-progress-reminder-1` |
| 2 | Reminder 1 was sent at least 14 days ago | `in-progress-reminder-2` |
| 3 | Reminder 2 was sent at least 14 days ago | `in-progress-reminder-3` |

All three require the survey to be in the `in-progress` state and to have no notification row of their
own type. The cutoff is `now − interval + 12 hours`, matching the scheduling tolerance used by other
reminders. Reminders 2 and 3 count from the preceding reminder's actual `sentAt`, including any delay
caused by weekends or recent contact. Bank holidays are treated as weekdays.

### Release date guard

`APP_IN_PROGRESS_REMINDERS_FROM` must be a valid `Y-m-d` date. Reminder 1 only selects surveys whose
`surveyPeriodEnd` is on or after it, so release does not send reminders to the abandoned backlog.
Reminders 2 and 3 follow an eligible first reminder and need no separate period-end check.

An unset, empty or invalid value stops all three reminders and logs an error. Disabling the family with
`APP_DISABLE_REMINDERS=csrgt-in-progress` also prevents it running. The `csrgt` family is separate:
disabling `csrgt` does not disable `csrgt-in-progress`.

### Seven-day contact gap

A candidate is held back when its most recent contact is after `now − 7 days`. The contact date is the
latest of:

- A manual reminder.
- A note marked `wasChased`.
- The audit-log entry for the move to `in-progress`.
- Any of the three automated in-progress reminders.

`ManualReminderHelper::getMostRecentEventDate()` supplies this date. The filter runs in PHP because the
in-progress date comes from the audit log. A held-back survey stays due and is selected on the first
weekday after the gap passes.

The rule also applies in the other direction: an automated reminder makes the manual reminder button
unavailable for seven days, with the `automated_reminder` explanation. A manual reminder is preferred
over its accompanying chased note when their dates tie.

### Entry points

Both entry points call `AutomatedNotificationsRunner::runOne()` for this family, which honours
`APP_DISABLE_REMINDERS`:

- Route `/cron/reminders/process-in-progress`, named `reminderprocessor_in_progress`.
- Command `app:cron:reminders:process-in-progress`.

The cron entry in `config/gcloud-build/all/cron.yaml` runs every day at 09:00 Europe/London. Weekend
runs prune old daily SMS counts but send nothing. `due($now)` is the shared selection method for the
real run, dry run and tests; its result includes due surveys, held-back surveys and any disabled reason.

Each survey is queued in its own transaction, with no delivery delay; `sentAt` is the run time.
Selecting at 09:00 avoids reminding surveys completed that morning. The consumer runs every five
minutes and does not re-check survey eligibility. Retries can therefore arrive after completion;
this is accepted.

## Recipients and templates

Each reminder has an email and SMS entry in `NotificationTemplates` for CSRGT only. The six template
references are `EMAIL_DOMESTIC_SURVEY_IN_PROGRESS_REMINDER_{1,2,3}` and
`SMS_DOMESTIC_SURVEY_IN_PROGRESS_REMINDER_{1,2,3}`. Templates can share wording and IDs; their
notification types remain distinct.

Email goes to the response contact email when present, otherwise the invitation email addresses.
The email blacklist applies to both sources. SMS goes to the response's contact telephone through
`SmsRecipientInterface::getSmsRecipientTelephone()`. Only the domestic survey implements that
interface; configuring an SMS template for a class without it is a programming error.

`NotificationSender::send()` processes email then SMS. It records one notification row per channel
sent and creates one message per recipient, carrying that row's ID and the survey's Notify reference.
The caller manages the transaction and flush.

If SMS is unavailable or capped, email still goes. Any sent channel makes that reminder complete:
a skipped SMS is not owed later. If neither channel can be sent, no notification row is recorded and
the survey stays due for the next weekday run. The admin notification tab shows each channel's row
and Notify responses, including failed SMS attempts.

### Mobile normalisation

`UkMobileNumber::normalise()` returns an eleven-digit `07…` mobile or `null`. It:

- Removes whitespace, hyphens, dots and brackets.
- Converts `+44`, `0044` and bare `44` international prefixes to national form, accepting an optional
  trunk zero after the prefix, bracketed or unbracketed.
- Accepts `07[1-5,7-9]…` and Isle of Man `07624…` mobiles; rejects `070` personal numbers, other
  `076` ranges, landlines, foreign numbers and malformed input.

For example, `07700 900123`, `+44 (0) 7700 900123`, `+447700900123` and `+4407700900123` all become
`07700900123`. This same form is sent to Notify and used for both queue-time and worker-time daily counts.

### SMS content

Personalisation includes the survey details, `passcode1`, `passcode2` and `loginLink`. An SMS can use
`((loginLink))` to open the login form with code 1 filled in; the respondent still enters code 2.

Keep each personalised SMS within one segment: 160 GSM-7 characters. Multipart messages cost more than
one unit at Notify, while these counters reserve one unit per message attempt. Template text and length
are managed in Notify; the application does not enforce segment length.

## Short login links

`LoginLinkToken` reversibly encodes the eight-digit code 1 (the passcode username) as five base-62
characters, using the alphabet `0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz`.
Tokens are left-padded with `0`; for example, `12345678` becomes `0pnfq`, and `00000000` becomes
`00000`. Encoding invalid usernames throws `InvalidArgumentException`.

Decoding requires exactly five alphabet characters and a value below 100,000,000. It returns the
eight-digit code, preserving leading zeros, or `null`. There is no database lookup, encryption or
dependency on `APP_SECRET`: code 1 is a username, and the token authenticates nothing.

- `GET /c/{token}` (`app_login_short`) redirects with HTTP 302 to `/login?c={token}`. The route accepts
  any non-empty path segment and leaves validation to the login page, so malformed non-empty tokens
  still reach the form.
- `GET /login?c={token}` decodes the token and prefills code 1 in its usual `1234-5678` format.
  Code 2 remains empty. Invalid tokens leave the normal empty form, without checking whether the
  username exists or adding a token error.
- Login POST handling is unchanged. Submitted values take precedence; after a failed login, the
  redirect has no token and neither code is prefilled.

Code 2 is never included in a URL. The URL contains only code 1, so access logs, browser history and
link previews cannot supply both credentials.

`PersonalisationHelper` generates `loginLink` as an absolute URL for every passcode survey type and
channel; templates can ignore it. The route uses the front-end hostname and requires HTTPS, so CLI,
HTTP cron and admin-host contexts all generate `https://<frontend host>/c/<token>`. HTTP requests to
the short route are redirected to HTTPS; local use therefore needs an HTTPS front end.

## SMS limits

Both limits count queue-time reservations and every further Notify attempt, including Messenger
retries, manual failed-queue retries and crash redeliveries.

| Limit | Setting | Default | Boundary |
|---|---|---|---|
| Annual allowance | `APP_SMS_ANNUAL_CAP` | 20,000 | 1 April, Europe/London |
| Per-number daily cap | `APP_SMS_DAILY_CAP_PER_NUMBER` | 5 | Midnight, Europe/London |

The daily cap prevents several surveys sharing a contact number, or repeat attempts, from sending too
many SMS to that number. Counters are independent of survey notification rows, so deleting a survey
does not give back allowance.

Both settings use `SmsCapSetting::parse()`: empty or null uses the default, a non-negative integer or
trimmed digit-only string supplies the cap, and everything else is invalid. **Zero or invalid means
stop all SMS**, including first attempts already reserved and queued. Invalid settings log an error;
a configured zero logs a warning when the worker refuses an attempt. Email and letter are unaffected.

### Storage

All three tables are written through DBAL; their entities keep Doctrine's schema mapping complete.

| Table | Key | Stored data |
|---|---|---|
| `sms_usage` | `financial_year` | `sent`: attempts reserved or charged in that year; the year is the calendar year in which the financial year starts |
| `sms_recipient_day` | `recipient_hmac, day` | `sent`: attempts reserved or charged to that number on that London day |
| `sms_attempt` | `notification_id` | Reserved `financial_year`, nullable `reserved_on` London date, and `attempts` started for the queued SMS |

`recipient_hmac` is a 64-character hex HMAC-SHA256 of the normalised mobile, keyed with `APP_SECRET`.
The daily counter does not store the number. A plain hash would permit enumeration of the limited
number space. Rotating `APP_SECRET` mid-day restarts that day's per-number counts; this is accepted.

Both `tryConsume()` methods conditionally increment their counter only while `sent < cap`. If the
row does not exist, they insert its first unit. This uses portable SQL for MySQL and SQLite, with no
upsert. Conditional updates cannot let concurrent callers take the same last unit. A simultaneous
first insert can fail with a duplicate key: at queue time the survey transaction rolls back and the
run aborts, leaving unsent surveys due; at worker time Messenger retries it. That retry can over-count.
The consumer's synchronized cron makes concurrent workers unlikely.

Annual rows and attempt records are retained. `DomesticInProgressReminders::run()` prunes daily rows
before today before checking the start date or weekend, logging the number deleted when non-zero.
A disabled family does not run or prune; its next enabled run clears old counts. Dry runs do not prune.

### Queue time

For an SMS with a usable recipient, `NotificationSender::send()`:

1. Reserves a daily unit using `SmsRecipientDailyCap::tryConsume($mobile, $sentAt)`. A refusal skips SMS
   and logs at info with the survey ID.
2. Reserves an annual unit using `SmsAllowance::tryConsume($sentAt)`. A refusal skips SMS and logs a warning.
3. Records the SMS notification row, calls `recordReservation()` with that row's ID and scheduled time,
   then creates and dispatches the message.

The ledger starts with `attempts = 0` and records the year and day prepaid for the first attempt.
Both counters, the ledger, notification rows and queued messages join the survey's transaction;
a dispatch failure rolls all of them back. When an annual refusal skips SMS after the daily unit was
taken, that daily unit remains consumed if the transaction commits. Charging daily first avoids
wasting an annual unit on a daily refusal. Delayed sends elsewhere use their scheduled `sentAt`.

### Worker time

`SmsAllowanceMiddleware` acts only on received SMS messages (`ReceivedStamp`); dispatch-side envelopes
and other channels pass through. Before Notify is called, it:

1. Checks the annual and daily caps for zero or invalid values. Either stops the attempt before the
   ledger is touched, even if the first attempt was prepaid.
2. Calls `SmsAllowance::recordAttempt()` to increment the ledger and return an `SmsAttemptRecord`.
   This step charges neither counter. A missing ledger row is inserted with `attempts = 1`,
   the current financial year, `reserved_on = null` and `wasMissing = true`.
3. Charges daily unless this is the first attempt on an existing row and its reserved day is today.
4. Charges annual unless this is the first attempt on an existing row and its reserved year is the
   current financial year.
5. Passes the message to the Notify handler.

| Attempt | Daily charge at handling | Annual charge at handling |
|---|---|---|
| First, on reserved day and year | None | None |
| First, next day in the same financial year | Current day | None |
| First, next financial year | Current day | Current year |
| Retry or crash redelivery | Current day | Current year |
| Missing ledger row | Current day | Current year |
| First on an existing row with unknown reserved day, same financial year | Current day | None |

Units reserved on an earlier day or year are not refunded. A daily unit charged before an annual
refusal is also retained. The ledger and worker counter writes are autocommitted before calling
Notify, so interrupted attempts remain counted. This includes failures Notify might not charge for
and crashes before the API call; the counters intentionally over-count rather than exceed the caps.
Notify references do not deduplicate sends, so an accepted request with a lost response can be charged
again on retry.

A cap refusal throws `UnrecoverableMessageHandlingException` directly, with no manufactured cause.
Messenger does not retry it: the message goes to `failed`, is acknowledged on `govuk-notify`, and
`AlphagovNotifyMessengerSubscriber` records a failed response on the notification row. Daily refusals
say `SMS per-number daily cap reached`; annual refusals include the financial year. Logs use a survey
ID or Notify reference, never the mobile number. Reaching the annual cap also logs a warning.

The subscriber falls back to the exception itself when `getPrevious()` is null. It still unwraps
handler failures and Notify's unrecoverable 400/403 errors to preserve the API error data and retry
status. Other Notify failures use Messenger's configured retries; crash redeliveries are identified
by the ledger rather than relying on `RedeliveryStamp`.

## Dry runs and rehearsal

`NotificationSender::preview()` returns message objects with notification ID `dry-run`, plus skipped
channels and their reasons. It records nothing, consumes neither real counter and dispatches nothing.
Its SMS budget callback receives the normalised mobile and returns `null` to allow the SMS or a skip reason.

`ReminderDryRun` calls the same `due()` selection and simulates both budgets across the run, daily before
annual. Each number's budget starts from its stored usage on the selected day, and surveys sharing
that number draw from the same budget. `--sms-cap` and `--sms-daily-cap` override the configured caps
for the simulation; settings and skip reasons appear in the output.

Daily usage is looked up with this checkout's `APP_SECRET`. Against a database written with a different
secret, those rows read as unused and the dry run may predict more SMS than the real run would send.
Shared numbers within the simulated run are still counted.

Rehearsals seed a freshly created scratch database. The seed command refuses a database that already
holds a seed or any operational data, including either SMS counter, so every rehearsal starts with fresh
budgets. Recreate the scratch database before another rehearsal, and drop it afterwards.
`app:dev:check-notify-templates` previews every SMS without charging limits.
`app:dev:send-test-sms` calls the Notify handler directly and bypasses both caps.
See [RemindersRehearsal.md](../RemindersRehearsal.md) for the commands and release checks.

## Configuration and rollout

| Setting | Where | Default |
|---|---|---|
| `APP_IN_PROGRESS_REMINDERS_FROM` | Deployed: Cloud Build substitution `_APP_IN_PROGRESS_REMINDERS_FROM`; local: `.env.local` | Unset: family sends nothing |
| `APP_SMS_ANNUAL_CAP` | `.env` / `.env.local`; no Cloud Build substitution | `20000` |
| `APP_SMS_DAILY_CAP_PER_NUMBER` | `.env` / `.env.local`; no Cloud Build substitution | `5` |
| `APP_DISABLE_REMINDERS` | Standard application configuration | Empty; add `csrgt-in-progress` to disable this family |

The SMS settings are bound into their services through `services.yaml`. Changing deployed values needs
a deploy. See [Env.md](../Env.md) for the full setting descriptions.

Migrations `Version20260923100000` and `Version20261001100000` create the annual counter and attempt
ledger, create the daily counter and add its reservation day. They remain separate so databases that
have already applied the first migration can advance normally. Notification types reuse the shared
notification tables.

Before enabling the family:

1. Create or verify the six Notify templates and configure their IDs in `Reference`. Check short links,
   passcodes and single-segment length after personalisation.
2. Add `_APP_IN_PROGRESS_REMINDERS_FROM` to every Cloud Build trigger; it can be empty until go-live.
3. Deploy migrations, application code and the 09:00 cron entry. Configure both SMS caps and disable
   `csrgt-in-progress` in environments where it must not send.
4. Set the period-end start date and redeploy to enable reminders, covering only the intended backlog.
   An enabled family with no valid date logs an error every day.

## Verification

The tests cover:

- Eligibility cutoffs, reminder sequence, start-date guard, seven-day contact gap, weekends, manual
  reminder availability, cron wiring and independent family disabling.
- Recipient fallback and blacklist filtering, mobile formats, one row per sent channel, shared numbers,
  cap refusals with email still sent, no-recipient surveys staying due, and transaction rollback.
- Annual and daily boundaries including London midnight and BST/GMT changes, independent numbers,
  lowered caps, invalid and zero settings, pruning, HMAC stability, and read-only remaining budgets.
- Prepaid first attempts, retries, crash redelivery without `RedeliveryStamp`, missing or undated ledger
  rows, emergency stops after queuing, terminal failure logging and wrapped Notify API errors.
- Login token round trips and invalid input, leading zeros, redirects, code-1-only form prefill,
  successful login with code 2, and HTTPS front-end links in CLI, cron and admin contexts.
- Dry-run budget overrides, skip summaries, unchanged database tables, template previews, and
  rehearsal seeding refusing an already seeded or populated database.

Relevant suites are `tests/Utility/Reminder/DomesticInProgressRemindersTest.php`,
`tests/Utility/Notification`, `tests/Messenger/SmsAllowanceMiddlewareTest.php`,
`tests/Messenger/SmsAllowanceMiddlewareWiringTest.php`, `tests/Utility/UkMobileNumberTest.php`,
`tests/Utility/LoginLinkTokenTest.php`, `tests/Functional/ShortLoginLinkTest.php`,
`tests/Utility/AlphagovNotify/PersonalisationHelperLoginLinkTest.php`, and the reminder command,
dry-run and rehearsal tests. Run PHPUnit with `APP_ENV=test`.
`tests/Messenger/AlphagovNotify/MessageHandlerTest.php` calls the real Notify service, so it is in the `notify-api` group, which is excluded by default (`--group notify-api` to run it).

## Scope

This feature does not include in-progress reminders for IRHS, pre-enquiry or RoRo; bank-holiday handling;
SMS opt-out or a separate mobile field; usage displays; grouping surveys into one SMS; carrying a skipped
SMS forward after sending email; rolling 24-hour caps; email caps; automatic SMS segment validation;
passcode login throttling; or links that authenticate or prefill code 2. Notify template wording is managed
separately.
