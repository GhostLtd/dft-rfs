# IRHS: require postcodes for trip origin/destination

- **Status:** Accepted (reviewed by Codex, 2026-11-02)
- **Date:** 2026-11-02
- **Branch:** `2026-11-01-mark-irhs-postcode`

## Context

Ticket: *"postcode stuff for IRHS — require postcodes, like CSRGT… just the vehicle level (start/end), not consignments"*.

CSRGT already does this, in two layers:

1. **Postcode changeover** (`Features::POSTCODE_CHANGEOVER` + `APP_POSTCODE_CHANGEOVER_DATE`, commits `37fceb93`/`0df6cd8f`/`0505ac14`/`517d600d`).
   `App\Utility\PostcodeChangeoverHelper::isPostcodeOnly(?Domestic\Survey)` decides per survey: flag off → free text; null
   survey / null `surveyPeriodStart` → free text; NI survey → free text; no date → postcode-only; otherwise
   `surveyPeriodStart >= changeover date`. Forms swap the field's label/help and add the constraint in `PRE_SET_DATA`.
2. **Outward-code check** (`Features::OUTWARD_CODE_CHECK`, commit `8b689a60`). `new Postcode(allowPartial: true,
   checkOutwardCode: true)` also requires the outward code to be in `data/uk-outward-codes.txt` (ONSPD-derived, includes BT).

In IRHS, the "vehicle level start/end" is `App\Entity\International\Trip::$origin` / `$destination` (free-text, 255 chars,
`NotBlank` in groups `trip_places` + `admin_trip`). The question is already UK-scoped ("Where **in the UK** did the trip
start?"; help says "postcode or place name"). The consignment-level place is `Action::$name` (loading/unloading places,
mostly abroad) and is **out of scope**.

### Where trip origin/destination are touched

| Area | File | Notes |
|---|---|---|
| Frontend wizard form | `src/Form/InternationalSurvey/Trip/OriginAndDestinationType.php` | Used by `config/workflows/international_survey/trip.php` (`STATE_ORIGIN_AND_DESTINATION`) for add **and** edit. No constructor today. |
| Admin add/edit form | `src/Form/Admin/InternationalSurvey/TripType.php` | Custom `TripDataMapper`; `origin`/`destination` are top-level fields. Used by `Admin\International\TripController` for add and edit; trip has its vehicle set before the form is built. |
| Entity | `src/Entity/International/Trip.php` | `NotBlank` + `Length(255)` — unchanged. |
| Display | `templates/international_survey/includes/trip.html.twig` | Already `capitalize \| formatPotentialPostcode`. Shared by frontend trip view, whole-survey view, admin survey view and delete pages. |
| Export | `src/Serializer/Normalizer/International/TripActionsExportNormalizer.php` | `OriginPlace`/`DestinationPlace` exported raw (CSRGT exports raw too). |
| Screenshots | `screenshots/src/international.js:171-172` | Types `Sandwich` / `Portsmouth`. `ScreenshotsCommand` only has a postcode mode for domestic. |
| Tests | `tests/NewFunctional/Wizard/International/{,TripDistanceDisabled/}Trip{,Change}Test.php`, `tests/DataFixtures/International/TripFixtures.php` | Use place names. `.env.test` sets no `APP_FEATURES`, so they stay in free-text mode and are unaffected. No existing `OriginAndDestinationType` form test. |

Survey reachability: `$trip->getVehicle()?->getSurveyResponse()?->getSurvey()` → `International\Survey`, which has
`getSurveyPeriodStart()` (via `HaulageSurveyTrait`). `TripAddController::getState()` sets the vehicle on the session trip;
`TripEditController::getState()` merges the session subject's changes into the freshly loaded trip
(`mergeTripChanges`) and uses that as the form subject, so the survey chain is always a managed entity. International
surveys have no NI flag.

## Decision

Mirror CSRGT: per-survey switch from "postcode or place name" to "postcode only" for trip origin and destination, on both
the frontend wizard and the admin trip form, with partial postcodes allowed and the outward-code check applied.

### 1. Gating: separate flag **and** separate date

- New flag `Features::IRHS_POSTCODE_CHANGEOVER = 'irhs-postcode-changeover'`.
- New env var `APP_IRHS_POSTCODE_CHANGEOVER_DATE` (compared to the IRHS survey's `surveyPeriodStart`, same semantics as CSRGT:
  unset/empty → all surveys; invalid → log error and fall back to free text).
- **Why not reuse `postcode-changeover` / `APP_POSTCODE_CHANGEOVER_DATE`:** the CSRGT date (2026-01-27 locally; in the past in
  prod) would flip every in-flight IRHS survey immediately, so respondents/admins editing an existing trip with a place name
  would be blocked. A separate flag also gives IRHS its own kill switch.
- The outward-code check reuses the existing global `outward-code-check` flag (it's a data-staleness kill switch, not a
  survey-type switch).

### 2. Helper

Extend `PostcodeChangeoverHelper` rather than adding a parallel class:

- Keep `isPostcodeOnly(?Domestic\Survey)` as-is (public API used by four CSRGT forms).
- Add `isIrhsPostcodeOnly(?International\Survey)`: flag `IRHS_POSTCODE_CHANGEOVER`, date `$irhsChangeoverDate`, no NI rule.
- Extract the shared "null start → false; no date → true; parse date (log + false on failure); compare" into a private
  method taking the start date, date string and env-var name (for the log message).
- `config/services.yaml`: add `$irhsChangeoverDate: '%env(default::APP_IRHS_POSTCODE_CHANGEOVER_DATE)%'`.

### 3. Frontend form — `OriginAndDestinationType`

- Inject `PostcodeChangeoverHelper` via constructor (autowired; form types are auto-registered).
- Move both fields into a `PRE_SET_DATA` listener (same pattern as `AbstractOriginType`). Survey from
  `$trip->getVehicle()?->getSurveyResponse()?->getSurvey()`.
- Postcode-only mode: label/help from new `*.postcode-only.*` keys, plus
  `constraints: [PostcodeChangeoverHelper::createIrhsPostcodeConstraint(['trip_places'])]` (a static factory returning
  `new Postcode(allowPartial: self::IRHS_ALLOW_PARTIAL_POSTCODE, checkOutwardCode: true, groups: $groups)`; shared with
  the admin form and the §7 check command).
- **Partial postcodes are a single switch:** `PostcodeChangeoverHelper::IRHS_ALLOW_PARTIAL_POSTCODE = true`, used by both
  the frontend and admin forms. Turning it off is a one-line change (plus help copy if it mentions partials). Form tests
  assert against the configured constant (a data provider can't change a class constant); the validator's behaviour with
  `allowPartial` true and false is already covered separately in `PostcodeValidatorTest`. (Not an env var — it's a
  product decision, not a deploy-time toggle.)
- Free-text mode: exactly as today.
- Input width: `govuk-input--width-10` in postcode mode (a postcode is at most 8 characters); free-text mode keeps
  `govuk-input--width-20`.
- Entity `NotBlank` still fires first with its existing message; `PostcodeValidator` returns early on empty.

### 4. Admin form — `Admin\InternationalSurvey\TripType`

- Add `PostcodeChangeoverHelper` to the constructor (alongside `PortsDataFactory`).
- **Only for surveys in postcode-only mode** (same `isIrhsPostcodeOnly($survey)` check as the frontend) — admins editing
  pre-changeover IRHS surveys keep free text.
- In postcode-only mode, add `PostcodeChangeoverHelper::createIrhsPostcodeConstraint(['admin_trip'])`
  and change labels to "Initial origin postcode" / "Final destination postcode" (agreed; CSRGT admin likewise swaps label +
  constraint). The fields are currently added in `buildForm` before the `PRE_SET_DATA` listener; either move them into
  that listener (watch field order in `templates/admin/international/trip/theme.html.twig` — it renders by name so order
  shouldn't matter) or add them in `buildForm` and re-`add()` with options in the listener.
- `TripDataMapper` maps by name — no change. Field-level constraints validate regardless of the custom mapper.

### 5. Translations (`translations/messages+intl-icu.en_gb.yaml`)

Under `international.trip.places`:

```yaml
origin:
  label: Where in the UK did the trip start?
  help: The postcode or place name **within the UK**, where this trip first started.
  postcode-only:
    label: Postcode where the trip started in the UK
    help: For example, HD1 3LE
destination:
  ...
  postcode-only:
    label: Postcode where the trip ended in the UK
    help: For example, HD1 3LE. This might be the same as where the trip started.
```

Copy is translation-only, so it can be changed later without code changes. Error message reuses `common.address.postcode.invalid` ("Enter a valid
postcode"), same as CSRGT.

### 6. Display / export

- No template change needed: `includes/trip.html.twig` already formats postcodes (incl. partial) via `formatPotentialPostcode`.
  Admin trip page headings: apply `formatPotentialPostcode` to the `origin`/`destination` heading params in
  `admin/international/trip/{edit,delete}.html.twig` (they currently use the raw values).
- Export stays raw, consistent with CSRGT. (Normalising on save is a separate decision for both surveys.)

### 7. Config / deployment / docs

- `cloudbuild.yaml` yarn-build env: `APP_IRHS_POSTCODE_CHANGEOVER_DATE=${_APP_IRHS_POSTCODE_CHANGEOVER_DATE}`, and add
  the `_APP_IRHS_POSTCODE_CHANGEOVER_DATE` substitution to the Cloud Build trigger(s). Add `irhs-postcode-changeover` to
  `_APP_FEATURES` when going live.
- `docs/Env.md`: document the new flag and date; broaden the `outward-code-check` description to cover IRHS trip
  origin/destination.
- `docs/ChangeLog.md`: `**Feature**: IRHS: postcode-only trip origin/destination`.
- `.env.local` (dev, not committed) can set the flag for manual testing.

**Choosing the changeover date.** A future date is not enough on its own. Edit access is state-based, with no
survey-start-date check:

- respondents — `SurveyVoter::EDIT`: new / invitation pending / invited / invitation failed / in-progress;
- admins — `AdminSurveyVoter::EDIT`: those active states **plus `closed`**, when the survey has a passcode user.

So trips may already exist on surveys whose `surveyPeriodStart` is after the chosen date, and they'd have to be converted
on the next edit. Before enabling, run a read-only check and move the date past anything it reports (or handle those
trips explicitly).

The check is a small console command (same pattern as `CheckLegacyNotifyMessagesCommand`), not hand-written SQL, so it
applies exactly the form's rule rather than approximating it with a regex (a regex would accept `ZZ1 1AA`, which the
outward-code check rejects):

- `app:irhs:check-postcode-changeover --date=YYYY-MM-DD` (read-only; no writes, no flush).
- Selects IRHS trips on surveys with `surveyPeriodStart >= date` and state in `SurveyStateInterface::ACTIVE_STATES` or
  `closed` (the union of what respondents and admins can edit). Reuse the voters' state lists or constants rather than
  re-typing state strings.
- Validates each trip's `origin` and `destination` with the Symfony validator against the same constraint the forms use,
  via one factory so the forms and the command can't drift: `PostcodeChangeoverHelper::createIrhsPostcodeConstraint(array
  $groups)` → `new Postcode(allowPartial: self::IRHS_ALLOW_PARTIAL_POSTCODE, checkOutwardCode: true, groups: $groups)`.
  **The command must pass `['Default']`:**

  ```php
  $constraint = PostcodeChangeoverHelper::createIrhsPostcodeConstraint(['Default']);
  $violations = $validator->validate($value, $constraint);
  ```

  `Postcode`'s constructor passes `groups` straight to the parent, so `groups: []` leaves the constraint with *no* groups
  and a plain `validate($value, $constraint)` silently skips it (verified: `Dover` gives zero violations with `[]`, one
  with `['Default']`). Keep `$groups` a required parameter so every caller has to choose.
- The outward-code part follows `Features::OUTWARD_CODE_CHECK` as in the forms, so run the command with the same
  `APP_FEATURES` as the target environment. Print the flag state in the output so a run without it is obvious.
- Output: a table of survey reference, period start, state, and the failing field/value; exit non-zero if anything fails.
- Unit-test it like `CheckLegacyNotifyMessagesCommandTest`, using the **real** validator (not a mock), so a groups mistake
  shows up as missed failures: a trip with a place name, a trip with an unknown district
  (`ZZ1 1AA`), and a valid trip; a closed survey is included; approved/exported surveys and surveys before the date are
  excluded.

This is a manual pre-enable check, not a deploy hook.

### 8. Screenshots

`screenshots/src/international.js` currently types place names. Add an IRHS postcode mode mirroring CSRGT's:
`locationMode` handling in `international.js` (as `domestic.js:148+` does), detection from
`Features::IRHS_POSTCODE_CHANGEOVER` in `ScreenshotsCommand` alongside the existing `$locationMode`, an
`international-postcode` output subdir, and an update to `docs/Screenshots.md`.

## Tests

- `tests/Utility/PostcodeChangeoverHelperTest.php`: IRHS cases — flag off; null survey; null `surveyPeriodStart`; no date;
  before/on/after date; invalid date (logs, returns false); CSRGT flag on but IRHS flag off → false (and vice versa).
  Existing CSRGT cases must still pass after the private-method extraction.
- New `tests/Form/Type/International/Trip/OriginAndDestinationTypeTest.php` (extends `AbstractTypeTestCase`, which already
  registers a real `PostcodeValidator`/`OutwardCodeChecker`; use `PreloadedExtension` to supply the type with a real
  helper):
  - free-text mode: place names valid; blank invalid.
  - postcode mode: full postcodes valid; partial postcodes valid/invalid according to `IRHS_ALLOW_PARTIAL_POSTCODE`
    (assert against the constant, so flipping it keeps the suite green); BT postcode valid; place name invalid; blank
    invalid.
  - postcode mode + `outward-code-check`: unknown district (`ZZ1 1AA`) invalid; terminated district (`BN4 1AA`) valid.
    Use full postcodes so these stay meaningful if partials are switched off.
  - trip with no vehicle → free-text mode.
  The domestic `PostcodeChangeoverTestTrait` is typed to domestic entities; add small IRHS mock helpers (or an IRHS
  sibling trait) rather than generalising it.
- Admin `TripType`: covered by a functional test rather than a form-type test (the type needs `PortsDataFactory`):
  admin edits a trip on a postcode-mode survey → place name rejected, postcode accepted; on a pre-changeover survey →
  place name still accepted.
- Functional: existing IRHS wizard tests stay in free-text mode (no flag in `.env.test`). Add BrowserKit tests with the
  flag enabled for **both** paths:
  - add-trip: submit a place name → rejected; resubmit with postcodes → accepted.
  - edit-trip (`/international-survey/trips/{id}/edit/origin-and-destination`) on an existing trip: submit a place name →
    rejected; resubmit with postcodes on the next request → accepted and persisted.
- `docker exec -w /home/wobble/web/work/dft-rfs php83 php vendor/bin/phpunit` and `phpstan` must pass.

## Risks

1. **Survey resolution in the wizard.** Low risk: add sets the loaded vehicle on the session trip, and edit merges session
   changes into the freshly loaded trip, so `isIrhsPostcodeOnly` always sees a managed survey. The edit functional test
   covers it anyway.
2. **Changeover date vs. surveys already being answered.** Edit access has no start-date check, so a future date can
   still catch future-dated surveys that already contain place-name trips; those respondents/admins would have to convert
   them on the next edit. Admins can also edit `closed` surveys. Pick the date using the pre-enable check command in §7.
3. **Partial postcodes.** CSRGT allows outward-code-only answers (e.g. `NN6`). IRHS does too, via one constant (§3).
4. **NI: no exemption (decided).** CSRGT exempts NI surveys (`0505ac14`, rationale not recorded). The inferred reason is
   that NI CSRGT journeys can cross into the Republic of Ireland: NI surveys alone get the `border-crossing` step
   (`config/workflows/domestic_survey/day_stop.php:117`, `day_summary.php:139`; help text cites the NI/Eire border), and
   CSRGT locations aren't restricted to the UK, so Eircodes would be rejected. IRHS trip start/end are explicitly
   "within the UK", so that doesn't apply; NI start/end points are BT postcodes, which are in the data file.
   International surveys have no NI flag anyway.
5. **Port-only answers.** Some respondents may currently enter a port name (e.g. "Dover") as start/end. Postcode-only
   forces a postcode; that's the intent of the ticket.

## Decisions (2026-11-02)

1. **Partial postcodes** — build with them allowed, behind one constant (§3), so it can be flipped either way.
2. **Go-live date** — chosen at deploy time.
3. **Copy** — "Postcode where the trip started/ended in the UK" (§5); translation-only, changeable later.
4. **Mandatory postcodes** — intended; no "don't know" escape hatch.
5. **NI** — included, no exemption (risk 4).
6. **Separate flag + date** (§1) — yes.
7. **Extend `PostcodeChangeoverHelper`** (§2) — yes.
8. **Admin constrained** — yes, only for surveys in postcode-only mode (§4).
9. **Screenshots** — add IRHS postcode mode (§8).
10. **Baseline data analysis** — not needed.

## Implementation notes (2026-11-02)

- `isPostcodeOnly()` (CSRGT) was refactored onto the shared private date check; its behaviour and existing tests are
  unchanged.
- Admin `TripType` now adds `origin`/`destination` in a `PRE_SET_DATA` listener (the theme renders them by name).
- The check command binds `--date` as `Types::DATE_MUTABLE`: bound as a datetime, MySQL compared the `DATE` column
  against `'… 00:00:00'` and missed surveys starting *on* the date.
- `IRHS_ALLOW_PARTIAL_POSTCODE` is a constant, so PHPStan sees the two ternaries on it (command output, one test) as
  always true; they carry `@phpstan-ignore ternary.alwaysTrue` because the constant is a deliberate switch.

## Out of scope

- Consignment-level places (`Action::$name`) — explicitly excluded by the ticket.
- UK/foreign port fields.
- Normalising/uppercasing stored postcodes or changing export format.
- Backfilling/converting existing place-name data.

## Estimate

Small–medium: ~2 form types, one helper method + constraint factory, a read-only pre-enable check command, translations,
config/docs, screenshots, plus tests. Comparable to the CSRGT changeover
commit minus the NI work and the four-form spread; the outward-code infrastructure is reused as-is.
