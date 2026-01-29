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

Supported values are: csrgt, irhs, pre-enquiry and roro.

### POSTCODE_CHANGEOVER_DATE

Used in conjunction with the `postcode-changeover` feature flag (`APP_FEATURES`). Controls when domestic surveys switch from accepting "postcode or place name" to "postcode only" for location fields.

- If **not set** (or empty): All surveys use postcode-only mode when the feature is enabled
- If **set** to a date (e.g., `2025-06-01`): Only surveys with `surveyPeriodStart` on or after this date use postcode-only mode

```
POSTCODE_CHANGEOVER_DATE=2025-06-01
```

See also: [Screenshots documentation](Screenshots.md) for generating screenshots with different location modes.
