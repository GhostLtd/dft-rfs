# CSRGT Vehicle Weights — Actions Taken to Reduce Input Errors

**Date:** 2026-06-11
**Companion to:** [2026-06-10 vehicle weights research](2026-06-10-vehicle-weights-research.md)

The research found that the CSRGT vehicle-weights form (`/domestic-survey/vehicle-and-business-details/vehicle-weights`)
was the single biggest source of form-validation errors, and that ~75% of the "Enter a number" failures were users
entering a **correct weight in a human-readable format** (`44,000`, `7.500`, `3,800kg`, `13.5 TON`).

This document records what we changed in response, and — just as importantly — the principle that constrained those
changes:

> **Reduce the error rate without ever silently storing a wrong number.** We only transform an input when its meaning is
> unambiguous. Anything genuinely ambiguous is left untouched and allowed to fail validation, so the user is asked
> again rather than having a guess saved on their behalf.

There are two strands: **validation correctness fixes** and **input normalisation**.

---

## 1. Validation correctness fixes

In `src/Entity/SimpleVehicleTrait.php` (the shared trait behind both the CSRGT frontend and admin vehicle forms):

- **Removed the redundant `Positive` constraint** on `grossWeight` and `carryingCapacity`. The `Range(min …)` of 3,500 /
  1,000 kg already rejects every non-positive value with a clearer message, so `Positive` only ever produced a confusing
  *second* error ("Enter a positive number" stacked on "must be at least 3,500 kg") for the same input.
- **Guarded the cross-field comparison.** `validateWeight()` now only compares carrying capacity to gross weight when
  **both have parsed to a real number** (`grossWeight !== null && carryingCapacity !== null`). This kills the
  "comma/comparison trap" from the research: previously, when gross weight failed to parse (e.g. `44,000`), the
  comparison still ran against the failed value and wrongly told users their carrying capacity was "more than" a gross
  weight it did not exceed. (Research recommendation #2.)
- **Reworded the comparison message** to *"Carrying capacity must be less than the vehicle's gross weight"* (translation
  key `common.vehicle.carrying-capacity.less-than-gross-weight`). The check fires on `capacity >= gross`, i.e. it
  requires capacity to be strictly **less than** gross — which is correct, since gross weight already includes the
  vehicle's own weight, so the two can only be equal if the vehicle weighed nothing. The old "cannot be more than"
  wording wrongly implied equal was allowed.

---

## 2. Input normalisation (the safe subset)

We normalise the raw submitted string **before** it reaches the integer parser, so correctly-intentioned input in a
human format is accepted. This is implemented as **three independent, opt-in form-type extensions** on the GOV.UK
integer type, each gated behind a per-field option (default off, so no other field's behaviour changes). They run on
`PRE_SUBMIT` in a fixed order and compose:

| Order (priority) | Option | What it does | Class |
|---|---|---|---|
| 1 (30) | `convert_tonnes` | explicit tonnes → kg | `App\Form\Extension\ConvertTonnesExtension` |
| 2 (20) | `strip_units` | remove a trailing unit (`kg`) | `App\Form\Extension\StripUnitsExtension` |
| 3 (10) | `strip_thousands_separators` | remove thousands separators | `App\Form\Extension\StripThousandsSeparatorsExtension` |

So `"3,500 kg"` → (units) `"3,500"` → (separators) `3500`, and `"3.8 T"` → (tonnes) `3800`.

The pure rules live in `src/Utility/Form/` (`ThousandsSeparatorNormaliser`, `UnitStripper`,
`TonnesToKilogramsNormaliser`) and are unit-tested exhaustively. The fields are wired in
`src/Form/DomesticSurvey/VehicleAndBusinessDetails/VehicleWeightsType.php`:

```php
'strip_thousands_separators' => true,
'strip_units' => ['kg'],
'convert_tonnes' => true,
```

### 2a. Convert explicit tonnes to kilograms

This is the one transformation that changes the *magnitude* of the value (×1000), so it carries the strictest safety
rails. It converts e.g. `3.8 T` → `3800`, `3,5 TON` → `3500`, `44 T` → `44000`, but only when **all** of these hold:

- **An explicit tonnes unit is present** (`t`, `ton`, `tons`, `tonne`, `tonnes`). A bare number is never converted — we
  never turn `44` into `44000`. (This is why unlabelled tonnes like `7.5`, ~the bulk of the residual errors, are
  deliberately *not* handled: `7.5` could be 7.5 t or a typo, and we won't guess.)
- **Any fractional part is 1–2 decimal places.** A thousands separator always produces groups of exactly 3 digits, so
  capping fractions at 1–2 digits guarantees a single `,`/`.` is a decimal point, not a thousands group. `3,5 TON` →
  3.5 t (decimal); `3,500 T` → ambiguous (could be a thousands group) → **left untouched**.
- **The tonnes value is below 100** — a sanity bound matched to real HGVs. `150 T` is left to fail rather than become
  150,000 kg.

The result is `tonnes × 1000`, rounded to the nearest kilogram (rounding only ever corrects floating-point noise such
as `3.8 × 1000 = 3799.999…`, since a 1–2 dp value ×1000 is always whole).

### 2b. Strip a trailing unit

Removes **one** trailing configured unit, case-insensitively, with optional preceding whitespace; longest unit first.
Only `['kg']` is configured for these fields, so `3500kg` / `3500 KG` → `3500`.

We **only strip units that map 1:1 to the stored unit (kg)**. Non-weight units like `cc` or `ltrs` are *not* stripped —
they aren't weights and should fail. And `ton`/`tonne` are *not* in this list, because stripping them would silently
accept `26 TON` as 26 kg; tonnes need *conversion*, not stripping (handled first, in 2a above).

### 2c. Strip thousands separators

Strips a separator **only** when the value is digit groups joined by a *single consistent* separator in standard
3-digit grouping:

- `^\d{1,3}(,\d{3})+$` → strip commas (`44,000` → `44000`, `188,188,188` → `188188188`)
- `^\d{1,3}(\.\d{3})+$` → strip dots (`7.500` → `7500`)
- anything else → **unchanged**

Deliberately left untouched (and so left to fail validation): mixed separators (`188,188.188`), non-3-digit groups
(`1,00`, `12,34,567`), and genuine decimals (`1.8`, `1,8`). We do not try to decide what an ambiguous value "probably"
means.

---

## What we deliberately did *not* do

Each of these was considered and rejected as too likely to store a wrong value (or out of scope):

- **Interpret unlabelled decimals as tonnes** (`7.5` → 7500). Addresses more residual errors, but `7.5` is genuinely
  ambiguous — we require an explicit unit instead.
- **Strip non-weight units** (`cc`, `ltrs`). These are wrong-domain values and should fail.
- **Resolve mixed/ambiguous separators** (`188,188.188`, `3,500 T`, `32,60`). Left to fail.
- **Convert tonnes ≥ 100 or with 3+ decimal places.** Out of the safe envelope above.

---

## Expected impact and what remains

From the research's normalisation simulation, the changes above remove roughly **a third of all validation errors**
(471 → 309), almost entirely from the two "Enter a number" rows (191 → 35, **−82%**), plus the cascade of bogus
comparison errors caused by an unparseable gross weight.

Normalisation **cannot** fix the largest *conceptual* problem — users entering the plated **gross** weight into the
**carrying capacity** box (≈80% of comparison failures, and increasingly dominant the more a user struggles). That needs
content/UX changes, which remain open (research recommendations #3–6):

- Clarify the carrying-capacity field ("maximum weight of goods the vehicle can carry, *not including the vehicle
  itself*").
- State units explicitly on the fields ("in kilograms, numbers only, e.g. 44000").
- A soft warning when carrying capacity equals (or is one less than) gross weight.
- A "not applicable / don't know" route for the zero-enterers.

---

## Reusing the normalisers elsewhere

The three extensions are opt-in and target the GOV.UK integer type generally, so any other integer field can enable any
combination by passing the relevant option(s) (`strip_thousands_separators`, `strip_units`, `convert_tonnes`) in its form
type. They default off, so enabling them is the only behavioural change.
