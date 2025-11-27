# IRHS and Pre-Enquiry LGV/Van Flow Changes (January 2026)

This document describes the changes made to add LGV (Light Goods Vehicle) / Van tracking to both the International Road Haulage Survey (IRHS) and Pre-Enquiry surveys.

## IRHS (International Road Haulage Survey)

### Wizard flow changes

| Old Flow                | New Flow                |
|-------------------------|-------------------------|
| intro-screen            | intro-screen            |
| contact-details         | contact-details         |
| **number-of-trips**     | **haulage-activities?** |
| business-details        | business-details        |
| **haulage-activities?** | **number-of-trips**     | 
| summary                 | summary                 |

This new flow terminates without asking business details if the company does not perform any 
international haulage activities. 

In addition, the "Number of trips" form now asks for both HGV and LGV journey counts separately:
1. Estimated annual HGV journeys (renamed from generic "journeys")
2. Estimated annual LGV journeys (new field)

(The rest of the survey only pertains to HGV journeys)

### Database view changes

- `view_international_firms`:
  - Renamed: `NumberOfInternationalJourneys` → `NumberOfInternationalHGVJourneys`
  - Added: `NumberOfInternationalLGVJourneys`
- `view_export_international_survey`: Same changes as above

### Export CSV changes

- Renamed: `numberOfIntJourneys` → `numberOfIntHGVJourneys`
- Added: `numberOfIntLGVJourneys`

## Pre-Enquiry

### Wizard Flow changes

The single "Vehicle details" screen has been split into two separate screens:

| Old Flow               | New Flow                  |
|------------------------|---------------------------|
| intro                  | intro                     |
| company-name           | company-name              |
| correspondence-details | correspondence-details    |
| correspondence-address | correspondence-address    |
| **vehicle-details**    | **hgv-details** (renamed) |
|                        | **lgv-details** (new)     |
| business-details       | business-details          |
| summary                | summary                   |

**HGV Details Screen:**
1. Total number of HGVs owned/used
2. Number of HGVs used for international journeys
3. Estimated annual international HGV journeys

**LGV Details Screen:**
1. Total number of LGVs/Vans owned/used
2. Number of LGVs used for international journeys
3. Estimated annual international LGV journeys

### Database view changes

**Database View (`view_export_pre_enquiry`):**

| Old Column Name | New Column Name |
|-----------------|-----------------|
| `NumberOfHgvs` | `NumberOfHgvs` |
| `NumberOfHgvsForIntJourneys` | `NumberOfHgvsForIntJourneys` |
| `EstimatedNumberOfIntJourneys` | `EstimatedNumberOfIntHgvJourneys` |
| *(new)* | `NumberOfLgvs` |
| *(new)* | `NumberOfLgvsForIntJourneys` |
| *(new)* | `EstimatedNumberOfIntLgvJourneys` |

### Export CSV changes

- Added fields:
  - `numberOfLgv`
  - `numberOfIntLgv`
  - `numberOfIntLgvJourneys`

(Equivalent HGV fields already had "Hgv" in their name: `numberOfHgv`, `numberOfIntHgv`, `numberOfIntHgvJourneys`)

---

## Migration Files

| Migration | Description |
|-----------|-------------|
| `Version20251202144841` | IRHS: Rename journey count column, add LGV column |
| `Version20260106124158` | IRHS: Update `view_international_firms` |
| `Version20260106124232` | IRHS: Update `view_export_international_survey` |
| `Version20260106160846` | Pre-Enquiry: Rename columns, add LGV columns |
| `Version20260106162725` | Pre-Enquiry: Update `view_export_pre_enquiry` |
