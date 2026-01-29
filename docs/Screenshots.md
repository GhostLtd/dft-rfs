[Home](../README.md) > Screenshots

# Screenshots

The screenshot generation system creates automated screenshots of all survey types for documentation purposes. It uses Puppeteer (via Node.js) to navigate through the surveys and capture full-page screenshots at each step.

## Prerequisites

- Must run in the **staging** environment (`APP_ENV=staging`)
- Node.js dependencies installed in `screenshots/` directory
- The staging database should have the required route data for RoRo surveys

## Command

```bash
bin/console rfs:dev:screenshots <mode> [path] [options]
```

### Arguments

| Argument | Required | Default | Description |
|----------|----------|---------|-------------|
| `mode` | Yes | - | Survey type to screenshot: `domestic`, `international`, `pre-enquiry`, `roro`, or `all` |
| `path` | No | `screenshots/screenshots.js` | Path to the Node.js screenshots script |

### Options

| Option | Default | Description |
|--------|---------|-------------|
| `--protocol` | `https` | Protocol to use (`http` or `https`) |

## Survey Modes

### domestic

Screenshots the CSRGT (Continuing Survey of Road Goods Transport) domestic survey. Creates a test survey with:
- Registration mark: `TE01 STT`
- Northern Ireland flag: enabled (to show border crossing questions)
- Survey period: 7 days from current date

### international

Screenshots the IRHS (International Road Haulage Survey) survey.

### pre-enquiry

Screenshots the pre-enquiry survey flow.

### roro

Screenshots the RoRo (Roll-on/Roll-off) survey. Creates test operators with routes:
- Test operator - Dover (routes to Calais and Dunkirk)
- Test operator - Harwich (route to Cuxhaven)

### all

Runs all four survey modes in sequence.

## Location Mode (Domestic Only)

The domestic survey supports two location input modes, determined by the `POSTCODE_CHANGEOVER` feature flag:

| Feature Flag | Location Fields Accept | Output Directory |
|--------------|----------------------|------------------|
| Disabled | "Postcode or place name" (e.g., "Flightston") | `domestic/` |
| Enabled | "Postcode only" (e.g., "SO14 0YG") | `domestic-postcode/` |

### Important: Web Server Environment

The screenshot tool works by making HTTP requests to the web server. This means the **web server's environment** (from `.env.local`) determines which mode the forms use - not environment variables set on the command line.

Setting `APP_FEATURES` on the command line only affects the console command itself, not the web server.

### Generating Place-Name Screenshots

1. Edit `.env.local` and remove `postcode-changeover` from `APP_FEATURES`:
   ```
   APP_FEATURES=dev-roro-auto-login
   ```
2. Clear the cache:
   ```bash
   bin/console cache:clear --env=staging
   ```
3. Run the command:
   ```bash
   APP_ENV=staging bin/console rfs:dev:screenshots domestic
   ```

### Generating Postcode Screenshots

1. Edit `.env.local` and add `postcode-changeover` to `APP_FEATURES`:
   ```
   APP_FEATURES=dev-roro-auto-login,postcode-changeover
   ```
2. Clear the cache:
   ```bash
   bin/console cache:clear --env=staging
   ```
3. Run the command:
   ```bash
   APP_ENV=staging bin/console rfs:dev:screenshots domestic
   ```

### Location Values Used

| Field | place-name mode | postcode mode |
|-------|----------------|---------------|
| Origin | Flightston | SO14 0YG |
| Destination | Destinstown | PO1 2UP |
| Furthest stop | Land's end | EX34 9EQ |
| Day stop (second) | Secondfield | BH1 1AA |
| Day stop (start) | Starttown | BS1 5TR |

## Output

Screenshots are saved to timestamped directories:

```
screenshots/output/YYYYMMDD-HHMMSS/
├── domestic/              # place-name mode
│   ├── landing.png
│   ├── login.png
│   ├── initial-details/
│   │   ├── 1-intro.png
│   │   ├── 2-contact.png
│   │   └── ...
│   ├── business-details/
│   ├── day-stops/
│   ├── day-stops-reorder/
│   ├── day-summary/
│   ├── dashboard/
│   └── final/
├── domestic-postcode/     # postcode mode (if run separately)
├── international/
├── pre-enquiry/
└── roro/
```

## Comparing Screenshots

The `screenshots/compare.sh` script compares two sets of screenshots and generates visual diffs:

```bash
cd screenshots
./compare.sh output/20240101-120000/domestic/ output/20240115-120000/domestic/
```

This creates comparison images showing:
- **Green**: pixels only in the reference (second folder)
- **Red**: pixels only in the comparison (first folder)
- **Grey**: unchanged pixels

Each image also displays a similarity score (100% = identical).

Output is saved to a `compared-to-*` subfolder within the first directory.

### Requirements for compare.sh

- ImageMagick (`convert` and `compare` commands)
- `bc` for floating-point arithmetic

## Examples

### Generate all screenshots (place-name mode)

```bash
APP_ENV=staging bin/console rfs:dev:screenshots all
```

### Generate domestic screenshots for both location modes

```bash
# 1. Edit .env.local: APP_FEATURES=dev-roro-auto-login (without postcode-changeover)
# 2. Clear cache and run:
bin/console cache:clear --env=staging
APP_ENV=staging bin/console rfs:dev:screenshots domestic

# 3. Edit .env.local: APP_FEATURES=dev-roro-auto-login,postcode-changeover
# 4. Clear cache and run:
bin/console cache:clear --env=staging
APP_ENV=staging bin/console rfs:dev:screenshots domestic
```

### Generate screenshots using HTTP (local development)

```bash
APP_ENV=staging bin/console rfs:dev:screenshots domestic --protocol=http
```

### Compare before/after a code change

```bash
# Before changes
APP_ENV=staging bin/console rfs:dev:screenshots domestic

# Make code changes...

# After changes
APP_ENV=staging bin/console rfs:dev:screenshots domestic

# Compare
cd screenshots
./compare.sh output/20240101-120000/domestic/ output/20240101-140000/domestic/
```

## Technical Details

### How it works

1. The PHP command creates a temporary user and survey in the database
2. It spawns a Node.js process running `screenshots/screenshots.js`
3. The Node.js script uses Puppeteer to:
   - Navigate to the frontend
   - Log in with the temporary credentials
   - Fill in forms and capture screenshots at each step
4. After completion (or on failure), the temporary user and survey are deleted

### Test data created

| Mode | Username | Data Created |
|------|----------|--------------|
| domestic | `screenshot` | PasscodeUser + DomesticSurvey |
| international | `screenshot` | PasscodeUser + InternationalSurvey + Company |
| pre-enquiry | `screenshot` | PasscodeUser + PreEnquiry |
| roro | `screenshot@example.com` | RoRoUser + Operators + OperatorGroup + Surveys |

### Timeout

The Node.js process has a 1-hour timeout to allow for slow page loads or large screenshot sets.

## Troubleshooting

### "must be running in staging app environment"

Set `APP_ENV=staging` when running the command.

### Screenshots fail partway through

Check `screenshots/output/*/error.png` for a screenshot of the page state when the error occurred. The console output will show which selector couldn't be found.

### Node.js errors

Ensure dependencies are installed:

```bash
cd screenshots
yarn install
```
