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
