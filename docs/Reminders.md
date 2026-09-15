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
* `channel` — the delivery channel: `letter` or `email`
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

Pre-enquiry is letter-only: the `EMAIL_PRE_ENQUIRY_*` template ids in `Reference` are empty strings, so
`NotificationTemplates` simply omits the email channel for pre-enquiry rather than sending an empty
template. RoRo has no invite and no manual reminder.

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

**`RoRoReminders`** — see [RoRo](#roro) below.

@see [DomesticReminders.php](../src/Utility/Reminder/DomesticReminders.php), [InternationalReminders.php](../src/Utility/Reminder/InternationalReminders.php), [PreEnquiryReminders.php](../src/Utility/Reminder/PreEnquiryReminders.php), [NotificationQueryHelper.php](../src/Utility/Notification/NotificationQueryHelper.php)

**Unscheduled notifications:**

* `invite` — sent when a diary keeper is invited; 4-hour dispatch delay for letter and email
* `reminder-manual` — sent via email from the admin panel for CSRGT and IRHS surveys

## Runner

`AutomatedNotificationsRunner::run()` is invoked once daily by cron (06:00 Europe/London). It receives every
service tagged `app.automated_reminders` — the four family classes, which implement `AutomatedRemindersInterface`
(`getFamily()`, `run()`) — through an autowired iterator, skips any family disabled by `APP_DISABLE_REMINDERS`,
and calls the rest. Adding a family is adding a class; the runner does not change.

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