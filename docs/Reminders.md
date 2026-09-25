[Home](../README.md) > Reminders

# Reminders and Notifications (CSRGT, IRHS, Pre-enquiry, RoRo)

Notifications are automated reminders and invites sent to diary keepers to remind them to start or continue their surveys. Each notification sent is recorded as a row in a survey-specific notification table, tracking the type, channel and timestamp.

## Storage

Notifications are stored in append-only tables, one row per send per channel:

* `domestic_survey_notification` — CSRGT surveys (CSRGT)
* `international_survey_notification` — IRHS surveys (IRHS)
* `pre_enquiry_notification` — Pre-enquiry surveys (PRE)
* `roro_survey_notification` — RoRo surveys

Each row records:
* `type` — the notification type (e.g. `reminder-1`, `invite`, `reminder-manual`)
* `channel` — the delivery channel: `letter`, `email` or `sms`
* `sent_at` — the timestamp when the notification was sent

@see [SurveyNotificationsTrait.php](../src/Entity/SurveyNotificationsTrait.php)

## What is sent

`NotificationTemplates` is a static table (survey class → notification type → channel → Notify template
id) and is the single place that knows which types apply to which survey class:

| type | CSRGT | IRHS | Pre-enquiry | RoRo |
|---|---|---|---|---|
| invite | letter, email | letter, email | letter | – |
| reminder-1 | letter, email | letter, email | letter | email |
| reminder-2 | letter, email | letter, email | letter | email |
| reminder-manual | email | email | – | – |
| in-progress-reminder-1 | email, sms | – | – | – |
| in-progress-reminder-2 | email, sms | – | – | – |
| in-progress-reminder-3 | email, sms | – | – | – |

Pre-enquiry is letter-only: there are no pre-enquiry email templates, so `NotificationTemplates` has no
email channel for pre-enquiry. RoRo has no invite and no manual reminder. The three in-progress reminders
share one email template and one SMS template: `EMAIL_DOMESTIC_SURVEY_IN_PROGRESS_REMINDER_{1,2,3}` all
point at the same Notify id, as do `SMS_DOMESTIC_SURVEY_IN_PROGRESS_REMINDER_{1,2,3}`. The notification type
still differs, so each reminder is scheduled and recorded separately.

`NotificationSender::send($survey, $type, ?$delay)` looks up the templates for the type, resolves
recipients from the type (invitation address / `NotificationInterceptionService::getAllEmailsForSurvey()`
for invite and the two reminders; the response contact email, falling back to the same helper and then
blacklist-filtered, for a manual reminder), records one notification row per channel actually sent, and
dispatches the Notify messages. `$delay` shifts the row's `sentAt` to the scheduled send time and adds the
matching `DelayStamp`; the only caller that passes one is the invite subscriber (`PT4H`). `send()` never
flushes and never opens a transaction — the caller owns both.

@see [NotificationTemplates.php](../src/Utility/Notification/NotificationTemplates.php), [NotificationSender.php](../src/Utility/Notification/NotificationSender.php)

## Eligibility

Each survey family is one class in `src/Utility/Reminder/` implementing `AutomatedRemindersInterface`, with public
query methods (`dueForReminder1($now)` / `dueForReminder2($now)`) that a test can call directly. The three
per-survey families (CSRGT, IRHS, pre-enquiry) extend `AbstractSurveyReminders`, which holds only the shared
execution machinery — `run()` (reminder-1 then reminder-2), the per-survey transaction and the log line, and the
12-hour cutoff helper — so each subclass is just its family name and its two eligibility queries. RoRo implements
the interface directly because its per-operator digests are a different shape. Two predicates, shared via `NotificationQueryHelper` so the DQL in each family
class stays readable, do the "not already sent" and "predecessor old enough" checks:

* `none($qb, $alias, $notificationClass, $type)` — the survey has no row of `$type`.
* `latestAtOrBefore($qb, $alias, $notificationClass, $type, $cutoff)` — the survey has a row of `$type`, and
  none newer than `$cutoff` (so a *resent* predecessor defers the following notification again).

**`DomesticReminders`** (CSRGT) and **`InternationalReminders`** (IRHS) are identical apart from the entity
classes they query:

* reminder-1: 7 days after the latest invitation, once the survey period has ended for 7 days, and only while
  no reminder-2 has been sent
* reminder-2: 14 days after the latest reminder-1

**`PreEnquiryReminders`**:

* reminder-1: 14 days after the latest invitation (no survey-period condition: a pre-enquiry has no period), and
  only while no reminder-2 has been sent
* reminder-2: 14 days after the latest reminder-1

All three restrict to surveys in the new/invitation-sent states and apply the existing 12-hour tolerance
(`cutoff = now − delay + 12h`), so a reminder due at 09:00 is not pushed to the next day's run by a few
hours' drift. Each survey is sent in its own database transaction.

**`DomesticInProgressReminders`** (CSRGT, in progress) is a separate family: three reminders to surveys that
were started but not completed, sent by email and SMS. It does not extend `AbstractSurveyReminders`.

* reminder-1: once `surveyPeriodEnd` is 7 days in the past, and only for surveys whose `surveyPeriodEnd` is on
  or after `APP_IN_PROGRESS_REMINDERS_FROM`
* reminder-2: 14 days after the latest reminder-1
* reminder-3: 14 days after the latest reminder-2

Each delay has the same 12-hour tolerance as the other families. If `APP_IN_PROGRESS_REMINDERS_FROM` is unset
or not a valid `Y-m-d` date, the family sends nothing at all and `run()` logs an error every run instead of
querying — going live takes two deliberate steps: deploy, then set the date.

A survey contacted in the last 7 days — a manual reminder, a chased note, the move to in-progress, or one of
these three reminders (via `ManualReminderHelper::getMostRecentEventDate()`) — is dropped from that run's due
list; it stays due and is sent on the first weekday run once the gap has passed. Nothing is sent on a Saturday
or Sunday (Europe/London); anything that falls due at the weekend is picked up by the Monday run.

This family is excluded from the 06:00 `AutomatedNotificationsRunner::run()`. It runs from its own entry point
at 09:00 — `app:cron:reminders:process-in-progress` / `/cron/reminders/process-in-progress` — via
`AutomatedNotificationsRunner::runOne()`, so that a survey completed that morning is no longer selected.

@see [CSRGT in-progress reminders, SMS limits and login links](features/2026-09-csrgt-in-progress-reminders.md)

**`RoRoReminders`** — see [RoRo](#roro) below.

@see [DomesticReminders.php](../src/Utility/Reminder/DomesticReminders.php), [InternationalReminders.php](../src/Utility/Reminder/InternationalReminders.php), [PreEnquiryReminders.php](../src/Utility/Reminder/PreEnquiryReminders.php), [NotificationQueryHelper.php](../src/Utility/Notification/NotificationQueryHelper.php)

**Unscheduled notifications:**

* `invite` — sent when a diary keeper is invited; 4-hour dispatch delay for letter and email
* `reminder-manual` — sent via email from the admin panel for CSRGT and IRHS surveys

## SMS allowance

SMS reminders draw on a shared annual allowance, `App\Utility\Notification\SmsAllowance`, backed by an
`sms_usage` table with one row per financial year (starting 1 April, Europe/London) and capped by
`APP_SMS_ANNUAL_CAP` (default 20,000, the Notify free tier). SMS is only attempted when the survey's
`SurveyResponse::contactTelephone` normalises to a UK mobile number (`App\Utility\UkMobileNumber::normalise()`);
anything else (landline, foreign number, none given) gets email only.

`NotificationSender` takes one unit of the current financial year's allowance when it queues an SMS, and in
the same transaction records an `sms_attempt` reservation row (`SmsAllowance::recordReservation()`), so the
ledger row and the counted unit always exist together. That pays for the message's first attempt at Notify.

Every further attempt at the same message — a messenger retry, a manual `messenger:failed:retry`, or a
Doctrine transport redelivery after a worker crash — is charged again by `App\Messenger\SmsAllowanceMiddleware`,
which runs before the Notify handler, records the attempt (`SmsAllowance::recordAttempt()`) and charges it. The
same applies to a first attempt that is only handled after the financial year has rolled over from the one that
reserved it. When the allowance is used up, the middleware fails the attempt terminally
(`UnrecoverableMessageHandlingException`): the message moves to the `failed` transport and its notification
row is shown as failed, rather than being retried forever.

Because an attempt can be charged without Notify ever confirming it, the counter is an upper bound on
chargeable SMS — it over-counts rather than under-counts, which is what keeps it a genuine hard cap.

### Per-number daily cap

No mobile number is sent more than `APP_SMS_DAILY_CAP_PER_NUMBER` SMS (default 5) per Europe/London calendar day,
which matters when several surveys share a contact number. `App\Utility\Notification\SmsRecipientDailyCap` counts
per number per day in `sms_recipient_day`, keyed on an HMAC of the number (with `APP_SECRET`), never the number
itself. It counts what the annual allowance counts: `NotificationSender` takes a unit when it queues an SMS (before
the annual unit), and `SmsAllowanceMiddleware` charges every further attempt, plus a first attempt handled on a later
day than it was queued (the ledger's `sms_attempt.reserved_on` records the day).

Over the cap at queue time, the SMS is skipped and the email still goes; with no email, nothing is recorded and the
survey stays due. Over the cap at handling time, the attempt fails terminally, as when the annual allowance is used
up. A cap of 0, on either limit, stops all SMS, including messages already queued.

Counts for earlier days are deleted at the start of each in-progress reminder run.

@see [SmsAllowance.php](../src/Utility/Notification/SmsAllowance.php), [SmsAllowanceMiddleware.php](../src/Messenger/SmsAllowanceMiddleware.php), [SmsRecipientDailyCap.php](../src/Utility/Notification/SmsRecipientDailyCap.php)

## Runner

`AutomatedNotificationsRunner::run()` is invoked once daily by cron (06:00 Europe/London). It receives every
service tagged `app.automated_reminders` — the family classes, which implement `AutomatedRemindersInterface`
(`getFamily()`, `run()`) — through an autowired iterator that excludes `DomesticInProgressReminders`, and calls
`runOne()` for each of the rest. `runOne(AutomatedRemindersInterface $reminders)` is the per-family body: skip
and log if `$reminders->getFamily()` is disabled by `APP_DISABLE_REMINDERS`, otherwise call `run()`. Adding a
family is adding a class; the runner does not change.

`DomesticInProgressReminders` is excluded from this 06:00 loop because it has its own 09:00 entry point (see
[Eligibility](#eligibility) above), which calls `runOne()` directly so the same disabled-family check applies.

Families run in no particular order: each family's eligibility depends only on its own tables, so any order
sends the same set. Within a family, `run()` sends its reminder-1 batch and then its reminder-2 batch, each
survey (or RoRo operator group) in its own database transaction; that inner order is for readability, since
the "own type not sent" and cutoff checks make the two batches independent of each other's results within a
run. Previously all CSRGT, IRHS and pre-enquiry reminder-1s were sent before any reminder-2, then RoRo, with
one flush at the end; the set a *completed* run sends is identical either way, but if a dispatch throws and
aborts the run, what has already been committed differs.

## Manual reminders

Manual reminders are sent via email only from the admin panel. They can only be sent for surveys in the __in progress__ state. Recipients are the survey's contact email, if provided, or the survey invitation emails.

A manual reminder can only be sent if at least __seven__ days have passed since the most recent of any of these events:

* Sending of a previous manual reminder
* Survey state change to in-progress
* A note marked as "chased"
* Sending of an automated in-progress reminder (CSRGT only)

@see [ManualReminderHelper.php](../src/Utility/Reminder/ManualReminderHelper.php)

## RoRo

`RoRoReminders` sends both reminders for every survey of the previous calendar month (Europe/London) that
has not been completed, on an active route still assigned to an active operator that has users, as one
digest email per operator user listing that operator's outstanding routes:

* **Reminder 1** — due as soon as a survey in last month's window has no reminder-1 row
* **Reminder 2** — 7 days after the *latest* reminder-1 row (12-hour tolerance), and no reminder-2 row yet

Candidates are surveys whose `surveyPeriodStart` falls in the calendar month before the run, joined to
operator/users/route/ports, then filtered in PHP to drop an inactive operator, an inactive route, or a
route no longer assigned to the operator. Due surveys are grouped by operator; per operator, inside one
transaction, `RoRoReminders` records one notification row per survey per channel and builds one digest
email per operator user (route names, most recent survey year/month, a per-user login link). The emails
originate from the `RoRoUser`, not a notification row, so `RoRoReminders` records rows and dispatches
messages itself rather than going through `NotificationSender`.

@see [RoRoReminders.php](../src/Utility/Reminder/RoRoReminders.php)

## Extending

To add a new automated notification: add a case to `NotificationType`, add a `NotificationTemplates`
entry per survey class that sends it, add a query method to the relevant family class (or a new family
class), and add its send loop to that family's `run()`.

## Response log

GOV.UK Notify's delivery/failure responses attach to notification rows, not to the survey. Each survey-type notification entity (e.g. `App\Entity\Domestic\SurveyNotification`) owns a many-to-many join to `NotifyApiResponse` via a per-type join table (e.g. `domestic_survey_notification_notify_api_responses`, `international_survey_notification_notify_api_responses`). RoRo is the exception: each reminder email originates from the recipient `RoRoUser`, not the survey, so RoRo `NotifyApiResponse` rows attach to the user, not to `roro_survey_notification` rows.

The admin panel shows a notification row as __pending__ (`notificationStatus()` in [invitation-details.html.twig](../templates/admin/includes/invitation-details.html.twig)) until at least one response has been recorded against it.

`AlphagovNotifyMessengerSubscriber` attaches each response to the notification row named by the message's originating entity (or to the `RoRoUser` for RoRo emails). It has no compatibility path for messages that name a survey directly: the release is deployed only once no such messages remain outstanding (see Deployment in [the feature write-up](features/2026-09-notifications-revamp.md#deployment)).

The legacy survey-keyed join tables (`domestic_survey_notify_api_responses`, `international_survey_notify_api_responses`, `pre_enquiry_notify_api_responses`) are dropped by `Version20260910130000`, after `Version20260910120000` has copied every link into the notification-keyed join tables.

@see [AlphagovNotifyMessengerSubscriber.php](../src/EventSubscriber/AlphagovNotifyMessengerSubscriber.php)

## Rehearsing before a release

[RemindersRehearsal.md](RemindersRehearsal.md) is a step-by-step local rehearsal, run before a release that
touches reminders. It uses four tools: `app:dev:seed-reminder-scenarios` seeds a scratch database with a survey
for every reminder scenario, due immediately; `app:cron:reminders:process` / `process-in-progress --dry-run`
previews what either cron would send without sending or writing anything; `app:dev:check-notify-templates`
validates every Notify template's personalisation without sending; and `app:dev:send-test-sms` sends one real
SMS to prove Notify delivery for this service.
