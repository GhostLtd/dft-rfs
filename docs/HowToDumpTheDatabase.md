# How to dump the database

The views do not dump cleanly, and as such, must be excluded from any database dump:

```bash
mysqldump \
  --set-gtid-purged=OFF \
  --single-transaction \
  --no-tablespaces \
  --skip-routines \
  --skip-triggers \
  -u road_haulage_surveys \
  -h cloud-sql-proxy \
  -P 3307 \
  -p road_haulage_surveys \
  --ignore-table=road_haulage_surveys.view_countries \
  --ignore-table=road_haulage_surveys.view_domestic_days \
  --ignore-table=road_haulage_surveys.view_domestic_stop_days \
  --ignore-table=road_haulage_surveys.view_domestic_summary_days \
  --ignore-table=road_haulage_surveys.view_domestic_survey_eligible_with_dates \
  --ignore-table=road_haulage_surveys.view_domestic_survey_vehicles \
  --ignore-table=road_haulage_surveys.view_export_domestic_driver_availability \
  --ignore-table=road_haulage_surveys.view_export_domestic_survey \
  --ignore-table=road_haulage_surveys.view_export_feedback_survey \
  --ignore-table=road_haulage_surveys.view_export_international_survey \
  --ignore-table=road_haulage_surveys.view_export_pre_enquiry \
  --ignore-table=road_haulage_surveys.view_export_roro_survey \
  --ignore-table=road_haulage_surveys.view_international_consignments \
  --ignore-table=road_haulage_surveys.view_international_firms \
  --ignore-table=road_haulage_surveys.view_international_trips \
  --ignore-table=road_haulage_surveys.view_roro_survey \
  --ignore-table=road_haulage_surveys.view_roro_vehicle_count \
  > live.sql
```

Also certain statements in the dump cause problems when reimporting, and must be filtered out:

```bash
sed -i '/\/\*!50013 DEFINER=/d' live.sql
```
