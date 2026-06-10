# RFS Vehicle Weights Form — Error Analysis

**Date:** 2026-06-10
**Source:** App Engine logs from `dft-sres-rfs-prod` (`downloaded-logs-20260610-110249.json`), 324 log entries covering 2026-05-11 onwards, cross-referenced against a fresh prod copy of the database.

All 324 log entries relate to the domestic survey **vehicle weights** form (`/domestic-survey/vehicle-and-business-details/vehicle-weights`), with errors on only two fields: `grossWeight` and `carryingCapacity`.

## Overall error counts

471 error instances across 9 distinct type/message pairs:

| Count | Field | Message |
|---|---|---|
| 125 | carryingCapacity | Carrying capacity cannot be more than the vehicle's gross weight |
| 97 | carryingCapacity | Enter a number |
| 94 | grossWeight | Enter a number |
| 80 | carryingCapacity | Enter the vehicle's carrying capacity |
| 25 | carryingCapacity | The vehicle's carrying capacity must be at least 1,000 kg |
| 25 | grossWeight | The vehicle's gross weight must be at least 3,500 kg |
| 18 | grossWeight | Enter the vehicle's gross weight |
| 6 | carryingCapacity | Enter a positive number |
| 1 | grossWeight | Enter a positive number |

## "Enter a number" (191 instances, 89 distinct values)

The overwhelming majority of these are users entering a **correct weight in a human-readable format**:

- **Comma thousands separators (~140 instances)** — `44,000` alone accounts for 41 instances; also `26,000`, `7,500`, `18,000`, etc. Sensible weights rejected purely for the comma.
- **Dot as a thousands separator (~25 instances)** — European-style formatting: `7.500`, `44.000`, `18.500`, `28.480`. These are almost certainly 7,500 kg etc., not decimals.
- **Weights in tonnes rather than kg (~15 instances)** — `7.5`, `3.8`, `26.5`, `13.5 TON`, `26 TON`.
- **Units appended (~15 instances)** — `7.500kg`, `3,800kg`, `28000kg`, `3500 kg`, `4500cc`, `18000ltrs`.
- **Genuinely unusable (~7 instances)** — `N/a`, `na`, the range `3000-3500`, `35.3/4`, likely-typo `32,60`.

**Takeaway:** stripping commas (and arguably a trailing "kg") before validation would eliminate roughly three-quarters of these errors. The tonnes entries suggest the field labels should state "in kilograms, numbers only, e.g. 44000" more prominently.

## "Enter a positive number" (7 instances)

All 7 are users entering zero: plain `0` (4 instances across both fields) or padded zeros (`00`, `0000`, `00000`). No formatting problem — these look like users trying to indicate "none / not applicable", with the padded variants resembling retries after the first `0` was rejected. May indicate the form needs an explicit "not applicable / don't know" route.

## "Carrying capacity cannot be more than the vehicle's gross weight" (125 instances, 93 users)

### What users entered

The top values entered as carrying capacity — `44,000` (44 instances), `32,000`, `18,000`, `40,000`, `26,000` — are exactly the standard UK plated gross weight limits (44t artic, 32t four-axle rigid, 26t three-axle, 18t two-axle). Spread across 93 users with nobody triggering it more than 4 times: a widespread comprehension problem, not a few stuck individuals.

### Confirmed against the database

For the 99 attempts where the gross weight field passed validation, the `_ghost_meta.user_identifier` was matched to `passcode_user.username` and joined through to the vehicle's saved weights (all 80 users matched after the prod refresh):

- **76 of 96 checkable attempts (79%): the entered carrying capacity exactly equals the vehicle's gross weight in the database.** Users are faithfully copying the plated gross weight into the carrying capacity box. The saved values make the misunderstanding vivid — e.g. one user entered 44,000 twice before ending up with a true carrying capacity of 1,000 kg.
- **Extra-zero typos of the correct answer (~4 attempts)** — `45000` → saved 4,500; `100000` → saved 10,000; `65000` → saved 6,500; `273200` vs gross 27,320.
- **Confusion in the other direction (~9 attempts)** — the entered carrying capacity matches the vehicle's *final saved carrying capacity*, meaning the gross weight box held a too-low value at the time (later corrected).
- The remaining handful are plain over-estimates.

### Suspect data now in prod

At least two users "fixed" the error by entering gross weight minus one (saved carrying capacities of 43,999 against gross 44,000, and 31,999 against 32,000). These passed validation but are almost certainly not real carrying capacities.

### Validation bug

In submissions where gross weight failed its own validation (e.g. `44,000` rejected by "Enter a number"), the comparison check still ran — apparently against a failed parse — so users were told their carrying capacity exceeded a gross weight it did **not** exceed (e.g. carrying `23,700` vs gross `44,000`). One user shaved their value to `43,999` and still got the comparison error, because it was never about the value. **Fix: skip the comparison when gross weight has not passed its own validation.**

## How many carrying capacity errors did each user encounter?

204 distinct users received at least one error on the carrying capacity field (of any kind). Counted two ways — by individual error instances (one submission can stack several errors on the field) and by submissions that contained at least one carrying capacity error:

| # errors | Users (by error instances) | % | Users (by submissions) | % |
|---|---|---|---|---|
| 1 | 141 | 69.1% | 141 | 69.1% |
| 2 | 34 | 16.7% | 36 | 17.6% |
| 3 | 17 | 8.3% | 18 | 8.8% |
| 4 | 5 | 2.5% | 3 | 1.5% |
| 5 | 2 | 1.0% | 3 | 1.5% |
| 6 | 2 | 1.0% | 1 | 0.5% |
| 7 | 1 | 0.5% | 1 | 0.5% |
| 10 | 1 | 0.5% | — | — |
| 14 | 1 | 0.5% | — | — |
| **Total** | **204 users / 333 errors** | 100% | **204 users / 321 submissions** | 100% |

So **141 users (69%) hit exactly one error and recovered immediately**, and 63 (31%) needed more than one attempt.

### What users were trying to enter, grouped by misunderstanding

Each user's rejected values were classified by the *underlying misunderstanding*, not by which error message fired. In particular, when the comparison error was caused by an unparseable value in the gross weight box (e.g. `44,000`), the user is counted under that format problem — a comma error is a comma error regardless of which box it was typed in. The gross weight saved in the database is used to identify "typed the gross weight". A user can appear in more than one batch, so percentages sum to more than 100%.

#### Users with exactly 1 error (141 users)

| Misunderstanding | Users | % |
|---|---|---|
| Submitted with a weight field blank | 37 | 26.2% |
| Put the vehicle's gross weight in the carrying capacity box | 35 | 24.8% |
| Commas as thousands separators | 28 | 19.9% |
| Tonnes instead of kilograms | 10 | 7.1% |
| Carrying capacity exceeded the (lower or invalid) gross weight entered at the time | 11 | 7.8% |
| Dots as thousands separators | 7 | 5.0% |
| Over-estimated above the gross weight | 6 | 4.3% |
| Units appended | 4 | 2.8% |
| Other plain number | 2 | 1.4% |
| Unparseable | 1 | 0.7% |

#### Users with exactly 2 errors (34 users)

| Misunderstanding | Users | % |
|---|---|---|
| Submitted with a weight field blank | 14 | 41.2% |
| Put the vehicle's gross weight in the carrying capacity box | 12 | 35.3% |
| Commas as thousands separators | 10 | 29.4% |
| Dots as thousands separators | 5 | 14.7% |
| Tonnes instead of kilograms | 4 | 11.8% |
| "N/a" | 2 | 5.9% |
| Units appended | 2 | 5.9% |
| Carrying capacity exceeded the (lower) gross weight entered at the time | 1 | 2.9% |
| A range (`3000-3500`) | 1 | 2.9% |

#### Users with 3+ errors (29 users)

| Misunderstanding | Users | % |
|---|---|---|
| Put the vehicle's gross weight in the carrying capacity box | 16 | 55.2% |
| Submitted with a weight field blank | 12 | 41.4% |
| Commas as thousands separators | 11 | 37.9% |
| Tonnes instead of kilograms | 7 | 24.1% |
| Zero or near-zero (`0`, `00000`, `1`) | 6 | 20.7% |
| Over-estimated above the gross weight | 3 | 10.3% |
| Carrying capacity exceeded the (lower or invalid) gross weight entered at the time | 3 | 10.3% |
| Units appended | 2 | 6.9% |
| Gross weight with an extra-zero typo (`273200`) | 1 | 3.4% |
| Dots as thousands separators | 1 | 3.4% |
| Other plain number (`500`) | 1 | 3.4% |

#### How the misunderstandings shift with persistence

The gross-weight-in-the-wrong-box misunderstanding becomes more dominant the more a user struggles: **24.8% of one-error users → 35.3% of two-error users → 55.2% of three-plus-error users**. It is the conceptual misunderstanding — an error message alone doesn't resolve it, so those users keep failing. Format problems (commas, dots, tonnes) stay roughly proportionate across cohorts, and blank submissions are most common among one-error users (likely accidental submits, instantly fixed). Notably, the **zero / giving-up batch only exists in the 3+ cohort** — entering `0` or `1` is what users do *after* repeated rejections, not a starting misunderstanding.

Two compounding behaviours worth noting:

- **The comma/comparison trap:** enter `44000` → "cannot be more than the gross weight" (the comparison ran against a comma-formatted, unparseable gross weight); reformat as `44,000` → "Enter a number". Both formats bounce, with no path forward.
- **One user, every format (40320678):** `3.8` (tonnes), `3.800` (dot separator), `3,800` ×6 (comma), `3,800kg` (units), `3.80` — fourteen errors over two days, all of them a correct answer of 3,800 kg in a rejected format.

## Simulated effect of input normalisation

What would have happened if the form had normalised input before validating, specifically:

a) stripping a trailing `kg` unit,
b) stripping commas and dots when used as thousands separators (`44,000` → `44000`, `7.500` → `7500` — but leaving genuine decimals like `7.5` and ambiguous values like `32,60` untouched), and
c) converting values with an explicit ton unit (`t`, `ton`, `tons`, `tonne`, `tonnes`), up to 2 decimal places and ≤ 100, to kilograms (`13.5 TON` → `13500`)?

Every logged value was re-run through the form's validation rules after normalisation (using the DB gross weight to evaluate the comparison rule where the gross field had passed). Results:

| Error | Before | After | Change |
|---|---|---|---|
| carryingCapacity: Cannot be more than the vehicle's gross weight | 125 | 115 | −8.0% |
| carryingCapacity: Enter a number | 97 | 23 | −76.3% |
| carryingCapacity: Enter the vehicle's carrying capacity | 80 | 80 | — |
| carryingCapacity: Must be at least 1,000 kg | 25 | 25 | — |
| carryingCapacity: Enter a positive number | 6 | 6 | — |
| grossWeight: Enter a number | 94 | 12 | −87.2% |
| grossWeight: Enter the vehicle's gross weight | 18 | 18 | — |
| grossWeight: Must be at least 3,500 kg | 25 | 29 | +16.0% |
| grossWeight: Enter a positive number | 1 | 1 | — |
| **Total** | **471** | **309** | **−34.4%** |

### Fate of the 191 "Enter a number" errors

| Outcome after normalisation | Instances | % |
|---|---|---|
| Parses cleanly — error eliminated | 152 | 79.6% |
| Still not a number (unlabelled tonnes like `7.5`, other units like `4500cc` / `18000ltrs`, `N/a`, ranges, `32,60`) | 35 | 18.3% |
| Parses, but reveals a genuine minimum-weight violation (e.g. `3,200` gross → 3200 < 3,500 kg) | 4 | 2.1% |

### Notes

- **A third of all validation errors disappear** (471 → 309), almost entirely from the two "Enter a number" rows (191 → 35, −82%).
- **The explicit ton conversion contributes 4 instances** (`26 TON`, `18 TON`, `13.5 TON`, `8.5 TON`), all of which convert to sensible kilogram values and pass cleanly — including against the comparison rule, since the affected users had entered both fields consistently in tons.
- **The comparison error only drops 8%** — but the composition changes meaningfully: the ~10 bogus instances (where an unparseable comma gross weight made a *correct* carrying capacity look too big) are eliminated, because the gross weight now parses and the comparison runs against the real number. What remains is overwhelmingly the genuine gross-weight-in-the-wrong-box misunderstanding, which normalisation cannot fix — that needs the field-label/help-text change.
- **The +4 on the gross minimum-weight rule is an improvement, not a regression**: those users previously got the unhelpful "Enter a number" for values like `3,200`; now they'd get the truthful message that their gross weight is below 3,500 kg.
- The required/blank, zero, and minimum-weight rows are untouched, as expected — normalisation only addresses format problems.
- Not simulated: stripping other units (`cc`, `ltrs` — arguably these *should* fail, as they aren't weights) or interpreting unlabelled decimals as tonnes (`7.5` → 7500). The latter would address most of the 35 residual "Enter a number" instances but is a riskier transformation (`7.5` could be a typo rather than tonnes); the explicit-unit conversion in (c) is the safe subset.

## Recommendations

1. **Normalise input before validating** — strip commas (and consider trailing "kg") from both weight fields; this alone removes ~75% of "Enter a number" errors and the bogus comparison errors that cascade from them.
2. **Skip the gross/carrying comparison when gross weight is invalid** — stops misleading stacked errors.
3. **Clarify the carrying capacity field** — help text along the lines of "the maximum weight of goods the vehicle can carry, not including the weight of the vehicle itself"; ~80% of comparison failures are users supplying the plated gross weight in good faith.
4. **State units explicitly** — "in kilograms, numbers only, e.g. 44000" to catch the tonnes entries.
5. **Consider a soft warning** when carrying capacity equals (or is one less than) gross weight — those saved values are likely wrong.
6. **Consider a "not applicable / don't know" route** for the zero-enterers.
