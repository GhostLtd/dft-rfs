[Home](../../README.md) > [Reminders](../Reminders.md) > Notification storage and sending

# Notification storage and sending

**Status:** implemented.

Survey invitations and reminders are recorded as notification rows, so adding a type does not require
another sent-date column on every survey table. Template selection and sending are shared; each survey
family owns its eligibility rules. See [Reminders.md](../Reminders.md) for schedules and recipients, and
[the CSRGT feature spec](2026-09-csrgt-in-progress-reminders.md) for in-progress reminders, SMS limits
and short login links.

## Notification rows

A row represents one send of one type over one channel for one survey. It stores `type`, `channel`
and `sent_at`. Repeated manual reminders and resent invitations create new rows, preserving history.
Several email recipients share one channel row, with a response for each recipient.

| Survey | Entity | Table |
|---|---|---|
| CSRGT | `Domestic\SurveyNotification` | `domestic_survey_notification` |
| IRHS | `International\SurveyNotification` | `international_survey_notification` |
| Pre-enquiry | `PreEnquiry\PreEnquiryNotification` | `pre_enquiry_notification` |
| RoRo | `RoRo\SurveyNotification` | `roro_survey_notification` |

These entities live under `App\Entity` and share a trait and interface. Their surveys implement
`SurveyNotificationsInterface`, which exposes history, newest-first rows of a type, first/latest send
dates and a check for whether a type has been sent. Admin lists, exports, overdue counts and SQL views
read this history; latest dates replace the latest values held by the former survey date columns.

`NotificationType` values match Notify event names. `NotificationChannel` supports letter, email and
SMS. `NotificationTemplates` maps survey class → type → channel → Notify template ID; a missing channel
is not sent. Pre-enquiry is letter-only; RoRo reminders are email-only.

## Sending and transactions

`NotificationSender::send()` selects templates and recipients, records one row per channel sent, and
dispatches one message per recipient. Channels with no recipients are skipped. If nothing is sent,
nothing is recorded and the survey stays due. SMS also requires capacity under both SMS caps.

Rows are recorded when messages are queued, rather than waiting for a Notify response. `sent_at` is
the scheduled send time: invitations use a four-hour `DelayStamp`; other sends have no delay.
Messages name their notification row as the originating entity, while their Notify reference remains
`{survey class}::{survey ID}::{recipient hash}` (without the final component when no hash exists).

The sender neither starts a transaction nor flushes. Its caller owns both. The Doctrine Messenger
transport writes `messenger_messages` on the ORM connection, so queued messages and notification rows
commit together. A dispatch failure rolls back both, including any SMS reservations.
`DispatchAtomicityTest` verifies this against the real transport.

Invitations are queued from the workflow's entered-invitation-pending hook, after the state marking has
been applied. The state, rows and messages therefore belong to the same unit of work; admin resends
wrap the workflow operation in a transaction. Manual reminders also use the sender, with their
availability checks, audit entry and chased note.

### RoRo digests

RoRo groups eligible surveys by operator and sends one digest email to each operator user. It records
a notification row per included survey and commits each operator's group in its own transaction.

`RoRoReminders` records and dispatches directly because each digest originates from its `RoRoUser`,
rather than one survey notification. Reminder and login email responses attach to that user.

## Reminder families

Each family implements `AutomatedRemindersInterface` and is tagged `app.automated_reminders`.
`DomesticReminders`, `InternationalReminders` and `PreEnquiryReminders` share execution machinery in
`AbstractSurveyReminders`; RoRo and CSRGT in-progress reminders implement their own send loops.

The family classes use `NotificationQueryHelper` for two history predicates:

- `withoutNotification()`: exclude surveys already sent a type.
- `withLatestNotificationBy()`: require the latest predecessor at or before a cutoff. Resending the
  predecessor therefore defers the following reminder.

`AutomatedNotificationsRunner::run()` runs the four standard families at 06:00 Europe/London.
`runOne()` skips families listed in `APP_DISABLE_REMINDERS`. The CSRGT in-progress family has its own
09:00 entry point using that same check. There is no runner lock; overlapping reminder runs remain
an accepted risk.

Each family sends its batches in reminder order, with one transaction per survey or RoRo operator
group. If a later dispatch aborts a run, earlier committed work remains; unsent work stays due.
Detailed eligibility and timing rules are in [Reminders.md](../Reminders.md#eligibility).

## Responses and admin display

`AlphagovNotifyMessengerSubscriber` attaches each Notify response to the message's originating
notification row, or to the user for RoRo emails. Invitation success/failure transitions apply to the
row's survey; responses do not set send dates.

The admin invitation/reminders tab groups rows by notification type, in `NotificationType::cases()`
order, newest first. A row with no response is pending and displays its scheduled send time.
Personal-data cleanup walks notification rows to remove letter content from their responses.

## Migrations

Apply these four migrations in order as one deployment:

| Migration | Purpose |
|---|---|
| `Version20260910100000` | Create the four notification tables; widen `notify_api_response.event_name` to 32 characters |
| `Version20260910110000` | Copy legacy sent dates into rows, then drop the date columns |
| `Version20260910120000` | Create notification-keyed response joins and copy the survey-keyed response links |
| `Version20260910130000` | Drop the survey-keyed response joins |

Backfilling uses response logs to identify channels. A date without a matching response is still copied:
manual reminders use email; other types use letter when an invitation address exists, otherwise email.
RoRo dates become email rows. Responses attach to the latest matching survey/type/channel row sent at
or before their timestamp, falling back to the earliest matching row when none precedes it.

Rollback restores date columns from each type's latest row and recreates the survey-keyed response
joins. Deployment recovery is from the pre-deploy backup: the migrations depend on each other and
must not be treated as independent releases.

Reapply `view_international_firms` and its dependent `view_export_international_survey` using
`app:views:apply` after migrating.

## Deployment

Messages queued by the previous release name a survey directly. They must be cleared before deploying:
the response subscriber has no compatibility path to attach them to notification rows, and an
invitation in flight can leave a survey without the history needed for later reminders.

1. Stop notification producers and let running producers and workers finish.
2. Check `messenger_messages` directly: no survey-originated messages may remain on `govuk-notify`
   or `failed`, including delayed invitations and retries. RoRo user-originated messages are distinct.
3. Take a backup, run the migrations and reapply the views.
4. Resume production and consumption, with the intended reminder families enabled.

Use [RemindersRehearsal.md](../RemindersRehearsal.md) to rehearse the release, and
[Env.md](../Env.md) for family configuration.

## Extending and verification

To add a notification, add its `NotificationType` case, template entries and the relevant family's
selection and send logic. A new family implements `AutomatedRemindersInterface`; include it in the
appropriate cron entry point. No new survey sent-date columns are needed.

The implementation is in `src/Entity/SurveyNotification*.php`, the concrete notification entities,
`src/Utility/Notification/`, `src/Utility/Reminder/`, the workflow and Notify subscribers, and
`templates/admin/includes/invitation-details.html.twig`.

Tests cover history ordering, template/type mappings, eligibility, recipient selection, dispatch
atomicity, invitation workflow handling, RoRo digests and admin rendering. See
`tests/Entity/SurveyNotificationsTraitTest.php`, `tests/Utility/Notification/`,
`tests/Utility/Reminder/`, `tests/Workflow/` and `tests/Twig/`. Run PHPUnit with `APP_ENV=test`.
`tests/Messenger/AlphagovNotify/MessageHandlerTest.php` calls the real Notify service, so it is in the `notify-api` group, which is excluded by default (`--group notify-api` to run it).
