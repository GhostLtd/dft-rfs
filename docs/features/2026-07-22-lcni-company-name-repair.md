# LCNI Company-Name Corruption — Investigation, Fixes and Data Repair

**Date:** 2026-07-20 → 2026-07-22

LCNI (Large Company Notification Interception) redirects survey correspondence for large companies to
centrally-managed email addresses. It matches on **exact company name** (case-insensitive, whitespace-collapsed on the
lookup side), so any corruption of the stored names silently disables the interception — surveys go to the sampled
address instead, with no error anywhere.

This document records the corruption we found, what caused it, what we changed, and why.

The principle that constrained the data repair:

> **Never destroy a record that a mechanical fix can turn back into a working interception; never "fix" a record whose
> correct form we cannot derive mechanically.** Repairs are renames where the repaired name is free; where the repaired
> name already exists, the damaged row is a dead duplicate and is deleted; anything ambiguous is left for a human. All
> data-changing behaviour is opt-in per flag, and everything supports `--dry-run`.

---

## 1. The newline jam (the bug that triggered the investigation)

**Symptom:** records with several company names crammed into a *single* `notification_interception_company_name` row,
newline-separated (e.g. `"Cannock Pallets Ltd\nCannock Pallets Limited\nCannock Pallets"`), with the previously-separate
alias rows deleted. 72 affected rows (holding 252 names) locally, all created between 2026-07-01 and 2026-07-15.

**Root cause:** the 2026-07-02 composer update (commit `44e62c45`) bumped symfony/form 6.4.36 → 6.4.42. Upstream
([symfony/symfony#62841](https://github.com/symfony/symfony/pull/62841), refined by
[#63154](https://github.com/symfony/symfony/pull/63154), first released in v6.4.42) made `TextareaType` normalise
submitted line endings CRLF/CR → LF *before* model transformers run. Our
`NotificationInterceptionCompanyNameDataTransformer` split the admin textarea on the literal `"\r\n"`, so it stopped
splitting entirely: the whole textarea became one name, and Symfony's collection diffing (orphanRemoval) deleted the
old alias rows in the same save.

**Fix:** split on `/\R+/u` (any Unicode line break, runs collapsed) — `NotificationInterceptionCompanyNameDataTransformer`.
A form test submitting LF-separated input now guards against regression.

## 2. The repair command

`php bin/console rfs:dev:lcni-fix-jammed-names [--dry-run] [--show-changes] [flags…]`

Repairs the damage already in the database. Design constraints:

- **Resumable / interruption-safe.** We see intermittent "MySQL server has gone away" (2006) failures on long-running
  commands. Each record is a self-contained unit of work (clear → reload → fix → flush) wrapped in retry-with-reconnect
  (see `MySqlGoneAwayRetryTrait`, extracted from `RecomputeCsrgtQualityFlagsCommand` so both commands share it).
  Because fixed rows drop out of the work-set query, a killed run is simply re-run and continues where it left off.
- **Everything is previewable**: `--dry-run` makes no changes; `--show-changes` prints each record's before/after
  (coloured, one blank-line-separated block per record).

### Phases and flags

| Phase | Flag | What it does |
|---|---|---|
| Jam fix | *(always runs)* | Splits newline-jammed rows back into one row per name: keeps and renames the existing row, adds the rest, drops parts already present, reports clashes with other records. |
| DVLA duplicate merge | `--merge-dvla-duplicates` | When a jammed name already exists on a *DVLA-import-created* record (dvla emails only, no manual csrgt/irhs emails), folds that record in: moves its aliases, merges its dvla emails, deletes it. Records with manually-entered emails are never merged — that would combine two configurations a human should reconcile. |
| Comma cleanup | `--clean-comma-names` | Two mechanical categories only: trailing comma (`"Acme Ltd,"`) and comma-appended postcode (`"Acme Ltd, WS119XG"`). Renamed to the clean form, or — where the clean form already exists anywhere — deleted as a dead duplicate (a damaged name can never match a lookup, so nothing working is lost). Deleting the last name of a record deletes the record; any emails not present on the surviving record are listed in a warning. Other comma shapes (joined variants, legitimate commas like `"BOURNEMOUTH, CHRISTCHURCH & POOLE COUNCIL"`) are deliberately untouched: commas can be part of real names, so auto-splitting is unsafe. |
| Double-space cleanup | `--clean-double-spaces` | Collapses runs of internal whitespace (double spaces, tabs, NBSP). Justification: the lookup collapses whitespace on the *input* side only, so a stored `"The Slush Shack  Ltd"` can never match. Same rename-or-delete-duplicate logic as the comma phase. Jammed rows are excluded (line breaks belong to the jam phase). |

**Why delete rather than merge for comma/space duplicates:** the damaged twin has never matched anything since
creation, so the clean-named record is by definition the one actually in service. Deletion reaches the same end state
as a merge with far less machinery, and the discarded-email warning surfaces the handful of cases where the dead
record held addresses the survivor lacks.

### Numbers from the staging copy (dry runs, 2026-07-21/22)

- Jam phase: 72 split, 8 clashes (all DVLA-created duplicates of manually-maintained records; `--merge-dvla-duplicates` resolves them).
- Comma phase: 289 comma rows → 39 renamed, 16 rows removed, 25 duplicate records deleted, 209 out of scope
  (categorised review list in `local/2026-07-21-lcni-comma-records.md`), 5 discarded-email warnings.
- Double-space phase: 258 → 234 renamed, 1 row removed, 23 duplicate records deleted, 14 discarded-email warnings.

## 3. Prevention: whitespace normalisation at the entity

`NotificationInterceptionCompanyName::setName()` now collapses runs of horizontal whitespace (space, tab, NBSP) and
trims — matching what `NotificationInterceptionRepository::normaliseBusinessName()` does to the lookup input. Since
every write path (admin form, DVLA import, repair command) goes through `setName()`, unmatchable names can no longer
be stored.

**Line breaks are deliberately preserved** by `setName()`: historical jammed rows contain them, and collapsing on an
incidental load-and-save would corrupt them *differently* (into space-joined names) instead of leaving them for the
repair command. Tests seed corrupted fixtures via raw SQL for the same reason.

**MySQL gotcha discovered here:** `utf8mb4_unicode_ci` treats NBSP as equal to a regular space, so
`LIKE '%<NBSP>%'` matches every name containing an ordinary space (~69k rows). Candidate fetching therefore over-selects
in SQL and does the definitive whitespace check in PHP.

## 4. DVLA import: CSV/XLSX parity

The import accepts CSV or XLSX. The paths pre-process separately and had drifted:

- **CSV** (never yet used in production — verified: no trace of its failure modes in the data) passed everything
  through raw: the header row (`Operator Name,Email address` — real DVLA CSVs have one, plus a UTF-8 BOM) would have
  been imported as a company called "Operator Name"; names untrimmed; emails not lowercased (breaking
  case-sensitive dvla-email dedupe on re-import).
- **XLSX** normalised correctly but **crashed on a blank row** (`Undefined array key "A"`).

Both paths now: strip BOM (CSV), validate the header (rejecting with the same error), skip blank rows, trim names,
trim + lowercase emails. A **parity test** (`NotificationInterceptionDvlaImportCsvTest`) feeds identical logical rows
through a real CSV and a real generated XLSX and asserts byte-identical output, so the implementations cannot drift
again — it caught the XLSX blank-row crash on its first run. Pre-processing is the only divergent layer; everything
downstream is shared code.

## 5. Left for humans (deliberately)

- ~209 comma rows: joined variants (`"Barnett Transport Ltd, Barnett Transport Limited"`) and possibly-legitimate
  commas — categorised with IDs and emails in `local/2026-07-21-lcni-comma-records.md`.
- 8 jam-clash / differing-email duplicate pairs where both records carry manual emails (e.g. Renault: hannah@ vs
  nigel@; Tapthat: …tankers vs …plumbers — possibly two businesses).
- Names with truncated leading characters from some historical import (`"ccluskey Limited"`, `"on Valley Plant Ltd"`,
  `"illiam M Walker Transport Ltd"`) — cause unknown, not mechanically repairable.

## 6. Test coverage added

- Transformer: line-ending matrix (CRLF/LF/CR/mixed/blank lines), entity reuse on edit.
- Command: 33 DB-backed tests covering split/dedupe/clash/merge/delete/dry-run/idempotent-re-run per phase.
- Entity: `setName()` normalisation matrix, including the preserved-newline contract.
- DVLA import: CSV normalisation, CSV/XLSX parity, header rejection for both formats.
