# Notifications Revamp (September 2026)

This document describes the rework of how survey invitations and reminders are stored, scheduled and sent
across CSRGT, IRHS, Pre-enquiry and RoRo. The motivation was that every new kind of notification had meant
adding another `*_sent_date` column to each survey table and another special case in three or four
classes. Notifications are now rows in a table, and every automated notification is driven by one shared
sending layer plus an explicit per-family class (see "Templates and eligibility" below for the current
structure).

There is no user-facing behaviour change: the same invites, reminders and manual reminders go out on the
same schedule to the same recipients. See [Reminders.md](../Reminders.md) for the operational reference;
this document explains what changed and why.

## Before and after

| | Before | After                                                                                     |
|---|---|-------------------------------------------------------------------------------------------|
| Where "sent" was recorded | Four nullable columns per survey table (`invitation_sent_date`, `first_reminder_sent_date`, `second_reminder_sent_date`, `latest_manual_reminder_sent_date`); RoRo had two of them | One row per send per channel (e.g. email, letter) in a per-survey-type notification table |
| When it was recorded | On the Notify API response (CSRGT/IRHS/PRE) or at dispatch (RoRo) | At dispatch, for every survey type                                                        |
| Where the rules lived | Template maps and hand-written DQL in `SurveyStateTransitionSubscriber`, `AutomatedRemindersHelper` and `ManualReminderHelper` | One class per family in `src/Utility/Reminder/`, with `NotificationTemplates` for what is sent |
| Where the Notify response log hung | Many-to-many from each survey entity | Many-to-many from each notification row                                                   |
| Adding a notification type | New columns, a migration, a new query, a new `switch` case, template maps | An enum case, a template-table entry per class, a query method and a loop in the family class |

## Storage

Each survey type has a notification entity following the existing `SurveyNote` pattern (a concrete
class per survey type sharing a trait and an interface):

| Survey type | Entity | Table |
|---|---|---|
| CSRGT | `App\Entity\Domestic\SurveyNotification` | `domestic_survey_notification` |
| IRHS | `App\Entity\International\SurveyNotification` | `international_survey_notification` |
| Pre-enquiry | `App\Entity\PreEnquiry\PreEnquiryNotification` | `pre_enquiry_notification` |
| RoRo | `App\Entity\RoRo\SurveyNotification` | `roro_survey_notification` |

A row is one send of one type over one channel for one survey: `type` (`invite`, `reminder-1`,
`reminder-2`, `reminder-manual`), `channel` (`letter`, `email`), `sent_at`. Rows are append-only; a
resent invitation or a repeated manual reminder is a new row, so history is kept. An email send to
several addresses is one row with several Notify responses attached.

The survey side (`SurveyNotificationsInterface`, implemented by all four survey entities) exposes
`getNotifications()`, `getNotificationsOfType()` (newest first), `getLatestNotificationSentAt()`,
`getFirstNotificationSentAt()` and `hasNotification()`. Everything that used to read a date column
(admin list pages, the CSRGT export, the IRHS export, the pre-enquiry overdue count, the
`view_international_firms` view) now reads rows, using the latest row of a type where the old column
held the latest value.

## Types and channels

`App\Utility\Notification\NotificationType` and `NotificationChannel` are string-backed enums. The type
values are deliberately the same strings as the existing Notify event names, so the `type` column and
`notify_api_response.event_name` agree. They replace the former `Reference::EVENT_*` constants.

The channel enum has an `sms` case that nothing produces yet; it mirrors the three message classes the
core bundle ships so the enum never has to change shape.

## Templates and eligibility

Every automated notification is driven by a static template table (`NotificationTemplates`), a thin sender
(`NotificationSender`), a shared query helper (`NotificationQueryHelper`), and one class per survey family
(`DomesticReminders`, `InternationalReminders`, `PreEnquiryReminders`, `RoRoReminders`) that owns its own
eligibility query and send loop. See [Reminders.md](../Reminders.md) for the operational reference.

`App\Utility\Notification\NotificationTemplates` is a static table (survey class → notification type →
channel → Notify template id) and is the single place that knows which types apply to which survey class.
`NotificationSender::send($survey, $type, ?$delay)` looks up that table, resolves recipients from the type,
records one notification row per channel actually sent, and dispatches the Notify messages.

| type | CSRGT | IRHS | Pre-enquiry | RoRo |
|---|---|---|---|---|
| invite | letter, email | letter, email | letter | – |
| reminder-1 | letter, email | letter, email | letter | email |
| reminder-2 | letter, email | letter, email | letter | email |
| reminder-manual | email | email | – | – |

Pre-enquiry is letter-only: the `EMAIL_PRE_ENQUIRY_*` template ids in `Reference` are empty strings, so
`NotificationTemplates` omits the email channel for pre-enquiry rather than sending an empty template.

Eligibility for the two scheduled reminders is an explicit query per family, built with two shared
predicates from `NotificationQueryHelper` (`none` — no row of a type yet; `latestAtOrBefore` — the latest
row of a type is at or before a cutoff, so a resent predecessor defers the following notification again):

| type | class | states | predecessor | anchor | delay (tolerance) | channels |
|---|---|---|---|---|---|---|
| reminder-1 | CSRGT, IRHS | new, invitation-sent | invite | surveyPeriodEnd | 7d (12h) | letter, email |
| reminder-1 | PRE | new, invitation-sent | invite | – | 14d (12h) | letter |
| reminder-2 | CSRGT, IRHS, PRE | new, invitation-sent | reminder-1 | – | 14d (12h) | letter, email (PRE: letter) |
| reminder-1 | RoRo | new, in-progress | – | – | – | email |
| reminder-2 | RoRo | new, in-progress | reminder-1 | – | 7d (12h) | email |

Every reminder also requires that no row of its own type exists yet, and the CSRGT, IRHS and PRE reminder-1
rules additionally require that no reminder-2 row exists.

RoRo also restricts candidates to surveys whose period falls in the previous calendar month, on an active
route still assigned to an active operator that has users, and groups due surveys per operator, building
one digest email per operator user; each operator's group is committed in its own transaction.

The 12-hour tolerance on every cutoff, and the display order in the admin panel (`NotificationType::cases()`
order), are unchanged. Two notifications remain unscheduled and are sent directly by their callers rather
than by a family's `run()`: `invite` (letter and email, with the existing four-hour dispatch delay) and
`reminder-manual` (email only, CSRGT and IRHS).

## Services

`NotificationSender::send(survey, type, ?delay)` resolves
recipients per channel (invitation address plus invitation/LCNI emails, or the response contact email for
manual reminders, blacklist filtered), skips a channel with no recipients (logged); a channel with no
template is absent from the table and never considered. It records one row per channel actually sent, and
dispatches the Notify messages. Invites carry the four-hour
`DelayStamp` and their row's `sent_at` is the scheduled send time. Each message's originating entity is its
notification row, but its Notify `reference` is set explicitly to the survey form the bundle used to build
(`{survey class}::{survey id}::{recipient hash}`), so references seen in Notify are unchanged. `NotificationSender` never flushes and
never opens a transaction; its caller does. RoRo bypasses `NotificationSender` entirely: `RoRoReminders`
records rows and dispatches its digest emails itself, because its messages originate from the recipient
`RoRoUser`, not the survey.

`AutomatedNotificationsRunner::run()` is still the nightly cron entry point (`app:cron:reminders:process`
and the `/reminders/process` cron route). It iterates every service tagged `app.automated_reminders` — the
four family classes, which implement `AutomatedRemindersInterface` (`getFamily()`, `run()`) — skipping any
family disabled by `APP_DISABLE_REMINDERS`, and calls each family's `run()`, which sends its own reminder-1
batch and then its own reminder-2 batch, each survey (or RoRo operator group) inside its own
`wrapInTransaction()`. Families run in no particular order, because each depends only on its own tables;
adding a family means adding a tagged class, not touching the runner. Previously `AutomatedRemindersHelper`
sent every CSRGT, IRHS and pre-enquiry reminder-1 before any reminder-2, then `AutomatedRoroRemindersHelper`
ran, each flushing once at the end: the set of notifications a *completed* run sends is identical either
way; what differs is which work is already committed if a later dispatch throws and aborts the run. There is
still no runner lock: the job runs once daily and normally
finishes quickly, each family's "own type not yet sent" predicate means a completed run is never repeated,
and two runs overlapping in time are an accepted exceptional risk.

Two notifications bypass the family classes and send directly from their callers:

- **Invites** are dispatched from the `entered.<invitation pending>` workflow hook (not the
  `transition.invite_user` hook it used to use), because Symfony applies the marking only after transition
  listeners return; on the entered hook the state change, the rows and the queued messages are in the
  same unit of work. Survey creation and import already flush inside `postPersist`; the admin resend
  controller now wraps its `apply()` in a transaction.
- **Manual reminders** send through `NotificationSender::send()` with `NotificationType::MANUAL_REMINDER`;
  availability rules, the audit-log entry and the "chased" note are unchanged.

## Transactions

The Notify transport is `doctrine://default`, so `messenger_messages` inserts happen on the same
connection as the ORM. Because every caller wraps dispatch-and-record in a transaction, a queued message
and its notification row become visible together or not at all. `tests/Utility/Notification/DispatchAtomicityTest.php`
proves this against the real transport: when the second channel's dispatch throws, the first channel's
already-inserted queue row and the rows roll back together.

## Notify response log

`notify_api_response` is unchanged as a table, but its many-to-many link moved from the three
non-RoRo survey entities onto their notification entities (join tables
`domestic_survey_notification_notify_api_responses` etc.). `AlphagovNotifyMessengerSubscriber` attaches
each response to the notification row the message names as its originating entity and applies the
`invitation_sent` / `invitation_failed` workflow transitions through the row's survey. It no longer
writes any dates. RoRo's digest emails are built by `RoRoReminders` for the operator's users, so they
originate from the `RoRoUser` (which now formally implements `ApiResponseInterface`) rather than from a
notification row; RoRo reminder and login emails both attach to the user as before.

The admin "Invitation/reminders" tab lists each notification row grouped by type in display order,
newest first, with its responses; a row with no response yet shows as **pending** with its scheduled send
time. The former queue-table scan (`PendingNotificationService`) is gone.

Personal-data cleanup now walks a survey's notification rows to strip letter content from responses.

## Migrations

Four plain migrations, run through `doctrine:migrations:migrate` and always as a set (each assumes the
state the previous one left; recovery is from the pre-deploy backup):

1. `Version20260910100000` creates the four notification tables and widens
   `notify_api_response.event_name` to 32 characters.
2. `Version20260910110000` back-fills one row per (survey, type, channel) from the legacy columns, using
   the response log to decide channels; a date with no matching response is still copied, as a letter when
   the survey has an invitation address and as an email otherwise (manual reminders always email), then
   drops the legacy columns. Rolling back restores the columns from the latest row per type.
3. `Version20260910120000` creates the notification-keyed join tables and copies every response link
   across, attaching each response to the notification row of the same survey, type and channel sent most
   recently at or before the response.
4. `Version20260910130000` drops the legacy survey-keyed join tables; rolling back recreates and refills
   them.

`view_international_firms` (and its dependant `view_export_international_survey`) was rewritten onto the
notification rows and must be re-applied with `app:views:apply` after migrating.

## Deployment

The subscriber has no compatibility path for messages queued by the previous release, which name a
survey rather than a notification row. The release is therefore deployed with no such messages
outstanding: stop producing notifications and let running producers and workers finish, confirm nothing
survey-originated remains on the `govuk-notify` or `failed` queues (including delayed invitations and
retries, checked in `messenger_messages` directly), back up, migrate, apply views, resume.

A survey-originated message handled after the release does not error: the survey still moves to
`invitation_sent`, but its response is attached to nothing, and an invitation still in flight at migration
time leaves the survey with no `invite` row, so it never becomes due for reminder-1.

## Configuration

Nothing new. `APP_DISABLE_REMINDERS` (comma-separated `csrgt`, `irhs`, `pre-enquiry`, `roro`) is honoured
as before, and is set per environment through the Cloud Build substitution.

## Extending

Adding a notification type is: a `NotificationType` case, a `NotificationTemplates` entry per survey class
that sends it, a query method on the relevant family class (or a new family class), and a loop for it in
that family's `run()`. The first planned addition, CSRGT in-progress reminders by email and SMS with an
annual cap, is designed separately and is not part of this change.

## Key files

- `src/Entity/SurveyNotification*.php`, `src/Entity/*/SurveyNotification.php`, `src/Entity/PreEnquiry/PreEnquiryNotification.php`
- `src/Utility/Notification/` — `NotificationType`, `NotificationChannel`, `NotificationTemplates`,
  `NotificationRecorder`, `NotificationSender`, `NotificationQueryHelper`, `AutomatedNotificationsRunner`
- `src/Utility/Reminder/` — `AutomatedRemindersInterface`, `AbstractSurveyReminders`, `DomesticReminders`, `InternationalReminders`, `PreEnquiryReminders`,
  `RoRoReminders`, `ManualReminderHelper`, `DisabledRemindersHelper`
- `src/EventSubscriber/SurveyStateTransitionSubscriber.php`, `src/EventSubscriber/AlphagovNotifyMessengerSubscriber.php`
- `src/Twig/NotificationExtension.php`, `templates/admin/includes/invitation-details.html.twig`
- `migrations/Version20260910100000.php` to `Version20260910130000.php`, `sql/views/view_international_firms.sql`
- Tests: `tests/Utility/Notification/` (`NotificationTemplatesTest.php`, `NotificationSenderTest.php`,
  `NotificationQueryHelperTest.php`, `AutomatedNotificationsRunnerTest.php`, `DispatchAtomicityTest.php`,
  `NotificationEnumsTest.php`), `tests/Utility/Reminder/` (`DomesticRemindersTest.php`,
  `InternationalRemindersTest.php`, `PreEnquiryRemindersTest.php`, `RoRoRemindersTest.php`,
  `ManualReminderHelperTest.php`), `tests/DataFixtures/Notification/` (`ReminderScenarioFixtures.php`,
  `InternationalReminderScenarioFixtures.php`, `PreEnquiryReminderScenarioFixtures.php`),
  `tests/Entity/SurveyNotificationsTraitTest.php`, `tests/Messenger/AlphagovNotify/MessageHandlerTest.php`,
  `tests/Twig/`
