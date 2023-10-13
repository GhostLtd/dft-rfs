[Home](../README.md) > Env file

# Env file

Local configuration can be set in .env.local, and overrides values in .env as per standard Symfony practises.

On GCP deployed instances, this configuration is set via the environment (Console > Cloud Build > Triggers > Edit > Substitution variables)

## Options

### ADMIN_USERS_PASSWORD_FILE

If present, this sets a path to a file containing "username: hash" pairs, which are used to authenticate users for the admin, when the site is running in DEV mode (only!):

```
wibble@example.com: $argon2id$v=19$m=65536,t=4,p=1$1MlYOr5ya0r4jRutXITJ1w$rtaS3vGchiKhMmShlkzqastgmGv2RWGw6BeAcPmJ38c
```

The hash strings for these pairs can be generated via the *security:encode-password* console command.

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
