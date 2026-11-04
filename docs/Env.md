[Home](../README.md) > Env file

# Env file

Local configuration can be set in .env.local, and overrides values in .env as per standard Symfony practises.

On GCP deployed instances, this configuration is set via the environment (Console > Cloud Build > Triggers > Edit > Substitution variables)

## Options

### MANAGEMENT_DOMAINS

If present, this setting contains a JSON string representing an array of domains. It is used to nominate users with the given domain names to be given maintenance privileges.

This allows some maintenance tasks to be achievable without needing to resort to direct database access. 

Currently these privileges only extend to being able to edit port names and codes.

```
MANAGEMENT_DOMAINS='["example.com","example.org"]'
```

### APP_DISABLE_REMINDERS

This setting takes a comma-delimited set of values, allowing for the disabling of reminders for different surveys.

Supported values are: csrgt, csrgt-in-progress, irhs, pre-enquiry and roro.

`csrgt-in-progress` is a separate family from `csrgt`: disabling `csrgt` does not disable `csrgt-in-progress`.

### APP_FEATURES

Comma-delimited list of enabled feature flags (see `App\Features`). Flags relevant to postcode handling:

- `postcode-changeover`: domestic survey location fields accept postcodes only (see `POSTCODE_CHANGEOVER_DATE` below).
- `irhs-postcode-changeover`: IRHS trip start/end (origin/destination, frontend and admin) accept postcodes only (see
  `APP_IRHS_POSTCODE_CHANGEOVER_DATE` below). Independent of `postcode-changeover`.
- `outward-code-check`: CSRGT trip-location postcodes (origin, destination, furthest stop, and their admin edit forms) and, in IRHS
  postcode-only mode, IRHS trip origin/destination must also have a
  known UK outward code (postcode district), as listed in `data/uk-outward-codes.txt` (current and terminated districts alike, since
  respondents may quote older paperwork). That file is generated from the ONS Postcode
  Directory by `bin/console rfs:dev:build-outward-codes` and should be regenerated when a new ONSPD release comes out. Disable the flag
  if the data file is stale and rejecting genuine postcodes.

```
APP_FEATURES=postcode-changeover,irhs-postcode-changeover,outward-code-check
```

### POSTCODE_CHANGEOVER_DATE

Used in conjunction with the `postcode-changeover` feature flag (`APP_FEATURES`). Controls when domestic surveys switch from accepting "postcode or place name" to "postcode only" for location fields.

- If **not set** (or empty): All surveys use postcode-only mode when the feature is enabled
- If **set** to a date (e.g., `2025-06-01`): Only surveys with `surveyPeriodStart` on or after this date use postcode-only mode

```
POSTCODE_CHANGEOVER_DATE=2025-06-01
```

### APP_IRHS_POSTCODE_CHANGEOVER_DATE

Used with the `irhs-postcode-changeover` feature flag. Controls when IRHS surveys switch trip start/end from "postcode or
place name" to "postcode only" (same rules as `POSTCODE_CHANGEOVER_DATE`, compared against the IRHS survey's
`surveyPeriodStart`; an invalid date logs an error and leaves every survey in free-text mode). Deployed via
`cloudbuild.yaml` (`_APP_IRHS_POSTCODE_CHANGEOVER_DATE` trigger substitution).

```
APP_IRHS_POSTCODE_CHANGEOVER_DATE=2027-01-04
```

**Before enabling**, pick the date with the read-only check: edit access isn't limited by survey start date, so surveys
starting on or after the date may already hold trips with place names. Run it with the target environment's
`APP_FEATURES` (so the outward-code check matches) and choose a date it reports nothing for:

```
bin/console app:irhs:check-postcode-changeover --date=2027-01-04
```

See also: [Screenshots documentation](Screenshots.md) for generating screenshots with different location modes.

### APP_IN_PROGRESS_REMINDERS_FROM

A `Y-m-d` date: the earliest survey period end (`surveyPeriodEnd`) eligible for CSRGT in-progress reminder 1.
Unset or invalid means none of the three in-progress reminders are sent (an error is logged on every run).

On deployed environments this comes from the Cloud Build substitution `_APP_IN_PROGRESS_REMINDERS_FROM` (set
it on each trigger — it may be left empty until go-live, which is treated the same as unset — then
redeploy). Locally it can be set in `.env.local`.

```
APP_IN_PROGRESS_REMINDERS_FROM=2026-10-01
```

In an environment where this feature isn't meant to run (e.g. one where the go-live date is deliberately
left unset), add `csrgt-in-progress` to `APP_DISABLE_REMINDERS` — otherwise the 09:00 run logs an error
every day while the date is unset.

### APP_SMS_ANNUAL_CAP

The number of SMS messages that may be sent per financial year (resets 1 April, Europe/London). Defaults to
`20000`, the GOV.UK Notify free allowance; empty means the default too. Every Notify attempt counts towards
the cap, including retries. Lower it if another service starts sharing the same Notify SMS allowance.

`0` stops all SMS from being sent, including messages already queued (they fail rather than going out). An
invalid value (anything other than empty or a non-negative whole number, e.g. `20,000`, `20k`, `-5`, `abc`) does
the same, and logs an error. Nothing else is affected (email and letter notifications carry on as normal).

It's set in `.env` / `.env.local`, not a Cloud Build substitution, so changing it needs a deploy.

### APP_SMS_DAILY_CAP_PER_NUMBER

The most SMS messages any one mobile number may be sent per day (a Europe/London calendar day). Defaults to `5`;
empty means the default too. Every Notify attempt counts, including retries. Once a number has reached the cap,
further reminders to it that day go by email only (or, with no email, stay due for the next run), and a retry that
would go over fails.

`0` stops all SMS, including messages already queued. An invalid value (anything other than empty or a
non-negative whole number) does the same, and logs an error. Email and letter notifications are unaffected.

It's set in `.env` / `.env.local`, not a Cloud Build substitution, so changing it needs a deploy.
