CREATE OR REPLACE VIEW view_roro_vehicle_count AS
SELECT
    vc.survey_id as SurveyID
    , if(vc.country_code is null, vc.other_code, vc.country_code) as CountryCode
    , COALESCE(vc.vehicle_count, 0) as VehicleCount
FROM roro_vehicle_count vc
JOIN roro_survey rs ON rs.id = vc.survey_id
WHERE rs.is_active_for_period = 1;
