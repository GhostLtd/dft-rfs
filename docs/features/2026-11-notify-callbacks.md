[Home](../../README.md) > [Reminders](../Reminders.md) > Notify callbacks and delivery problems

# Notify callbacks and delivery problems

**Status:** implemented (phase 1). Requires `ghost/govuk-core-bundle` ^6.2 for the reference prefix.

GOV.UK Notify tells us what happened to a message after it accepted it: email and SMS **delivery receipts**, and
**returned letters**. We store these against the response they're about, and show them in admin with any
send-time failures. There, admin users can see a survey's **delivery problems**, mark them seen, and filter the
CSRGT, IRHS and pre-enquiry lists by them.

There are no behaviour changes based on callbacks: nothing is blacklisted, suppressed or transitioned. Related
changes in the same release:

- **Retry fix:** a send failure that Messenger will retry is now recorded as `waiting-for-retry`, not `failed`.
  Before, a retried non-API failure, such as a network error, applied `invitation_failed` too early, and a later
  successful retry couldn't move the survey on to `invitation_sent`.
- **SMS cap refusals:** these are recorded as `refused`, not `failed`, so they aren't delivery problems. The
  number was never tried.
- **Reminder age limit:** CSRGT in-progress reminders stop once the survey period ended more than 56 days ago.
  See [Reminders](../Reminders.md).

## Outcomes

| Outcome | Source | Kind | Problem? | Admin |
|---|---|---|---|---|
| final `failed` response (Notify rejected it, or a failure Messenger won't retry) | send time | send-time | yes | red `failed` tag (existing) |
| email/SMS `permanent-failure` | delivery receipt | after sending | yes | red tag (grey once seen) |
| email/SMS `technical-failure` (not sent) | delivery receipt | after sending | yes | red tag (grey once seen) |
| returned letter | returned-letter callback | after sending | yes | red "letter returned", with the date we were notified |
| email/SMS `temporary-failure` (full inbox, phone off) | delivery receipt | after sending | no | yellow tag |
| `refused`: SMS refused by our own caps (annual allowance, per-number daily cap) | send time | — | no: the number was never tried | grey tag, with the reason |
| `delivered` | delivery receipt | — | no | green tag, with delivery time |

Letters get no delivery receipt; a returned letter can arrive weeks after sending.
`NotifyApiResponse::hasDeliveryProblem()` and `NotifyCallback::FATAL_STATUSES` define what counts as a problem.

## Storage

- `notify_api_response.notify_id`: Notify's own id for the notification, which is what callbacks refer to. It's
  set on accepted responses, and the migration backfills it from the `data` JSON.
- `notify_api_response.problem_seen_at`: when an admin marked this response's problem as seen. It sits on the
  response, so it covers both kinds of problem.
- `notify_callback` (`NotifyCallback`): one row per callback, linked to its response and deleted with it.
  - The payload is stored without the recipient (`to`).
  - `status` is Notify's status, or `returned` for returned letters, whose payloads have none.
  - `(notify_id, type, status)` is unique, so a callback Notify sends again is stored once.

## The endpoint

`POST /notify/callback/delivery-receipt` and `POST /notify/callback/returned-letter`, on the frontend host
(`NotifyCallbackController`, processing in `CallbackProcessor`):

| Response | When |
|---|---|
| 204 | stored, a duplicate, or not ours (matched no response; nothing stored) |
| 401 | missing or wrong bearer token, or no token configured |
| 400 | malformed payload (not a JSON object, a missing/invalid id, an unknown delivery status) |
| 503 | the maintenance lock is on (`MaintenanceLockSubscriber`) |
| 500 | anything unexpected, e.g. the database |

401s and 400s are logged as warnings, because Notify never sends those callbacks again. The body isn't logged,
since it holds the recipient.

The route has its own firewall (`notify_callback`, `security: false`) and is `stateless`. Nothing may start a
session there. With debug on, Symfony turns session use on a stateless route into a 500. For the same reason,
`FormWizardCleanupSubscriber` skips requests without an existing session.

Notify waits 5 seconds. It sends a callback again after a network error, a timeout, a 5xx or a 429, every 5
minutes up to 5 times, but **drops any other 4xx**. So anything temporary must be a 5xx. The maintenance page is
a 200, which Notify would take as accepted, so callbacks get a 503 during maintenance instead.

A stored callback with a fatal outcome clears its response's `problem_seen_at`, in the same transaction, so the
problem shows as unseen. A duplicate doesn't, so an admin's "seen" survives Notify sending a callback again.

### Environments

All environments share **one** Notify service, using different API keys, and a service has one callback URL.
So prod receives every environment's callbacks. A callback that matches no response is answered with a 204
and not stored. To tell them apart, every non-prod Notify reference is prefixed with the environment
(`APP_NOTIFY_REFERENCE_PREFIX`, e.g. `dev::App\Entity\Domestic\Survey::…`):

- An unmatched callback from another environment, or with no reference, is logged at info.
- One carrying this environment's own prefix (none, on prod) shouldn't happen, so it's logged as a warning.

Survey and RoRo digest references get the prefix in the bundle's `MessageHandler` (6.2.0+), whose
`$referencePrefix` is bound in `config/services.yaml`. RoRo login emails get it in
`LoginMessageHandler`, which sends the prefix alone, as login emails have no reference of their own.

Dev and test can't receive real callbacks once prod's URL is set. Test them with the functional tests, or, before
go-live, by pointing the service's callback URL at a tunnel or dev for a short window. While it points elsewhere,
prod's receipts, which include recipients' addresses, go there too.

## Admin

- **Survey view**, Invitation/reminders tab:
  - each response shows its callbacks' outcomes;
  - a summary ("2 delivery problems (1 unseen)") with a **Mark delivery problems as seen / unseen** button
    (`DeliveryProblemsController`) sets or clears `problem_seen_at` on all the survey's problem responses.
- **Lists**: CSRGT (GB/NI), IRHS and pre-enquiry get a **Delivery** column.
  - The cell shows the number of problems: red while any are unseen, green once all are seen. Counts are
    loaded in one query per page (`DeliveryProblems::countsForSurveys()`).
  - The filter (`DeliveryProblemsChoiceFilter`) offers Any / Send-time / After sending, each also as "(unseen)".
  - To make room, the CSRGT "Quality flags" and "In possession?" filters became "Flags" and "Possession?", with
    shorter options. Their query-string names changed to `flags` and `possession`. Reg marks no longer wrap.
- RoRo: callbacks for RoRo users' responses are stored, but not shown.

## Deployment

1. **Cloud Build:** add substitutions to every trigger. See [Env.md](../Env.md).
   - `_APP_NOTIFY_CALLBACK_TOKEN`: a long random secret per environment.
   - `_APP_NOTIFY_REFERENCE_PREFIX`: `dev` / `test`, and empty on prod.
2. **Migrate:** `Version20261105100000` adds the columns and table, and backfills `notify_id` (about 150k accepted
   responses on the 2026-10 prod copy). It also relabels past SMS cap refusals, recorded as `failed`, as
   `refused`. They're matched on the two refusal messages; there were none on the 2026-10 prod copy, which predates
   SMS. It can be re-run after a failure.
3. **Notify:** in the service's *API integration → Callbacks*, set:
   - the delivery receipts URL to `https://<frontend host>/notify/callback/delivery-receipt`;
   - the returned letters URL to `…/notify/callback/returned-letter`;
   - the bearer token to prod's `APP_NOTIFY_CALLBACK_TOKEN`.
4. **Check:** send to a team member's address and check that "delivered" appears on the survey. Returned letters
   can only be checked in prod, when the first ones arrive. Compare them with Notify's returned letters report.

## Tests

- `tests/NewFunctional/NotifyCallbackTest.php` covers the endpoint: tokens, payloads, duplicates, seen state,
  maintenance, and server errors.
- `tests/Utility/AlphagovNotify/Callback/CallbackProcessorTest.php` covers log levels for unmatched callbacks.
- `tests/NewFunctional/Admin/DeliveryProblems*Test.php` cover the admin view and lists, and
  `tests/ListPage/Field/DeliveryProblemsChoiceFilterTest.php` covers the filter.
- `tests/EventSubscriber/AlphagovNotifyMessengerSubscriberTest.php` covers the retry fix and `notify_id`, and
  `tests/Messenger/AlphagovNotify/LoginMessageHandlerTest.php` covers the login-email prefix.
