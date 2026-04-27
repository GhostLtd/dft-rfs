CREATE OR REPLACE VIEW view_roro_survey AS
SELECT
    rs.id AS SurveyID
    , YEAR(rs.survey_period_start) AS Year
    , CEIL(MONTH(rs.survey_period_start) / 3) AS Quarter
    , MONTH(rs.survey_period_start) AS Month
    , CONCAT(LPAD(rup.code, 2, '0'), LPAD(rfp.code, 2, '0'), LPAD(ro.code, 2, '0')) AS RoRoNo
    , LPAD(ro.code, 2, '0') AS OperatorCode
    , rs.is_active_for_period AS IsActiveForMonth
    , rs.state AS SurveyState
    , CASE 
        WHEN rs.is_active_for_period = 1 THEN 
            COALESCE(SUM(IF(rvc.other_code IS NULL OR rvc.other_code <> 'trailers', rvc.vehicle_count, NULL)), 0)
    END AS TotalPoweredVehicles
    , CASE 
        WHEN rs.is_active_for_period = 1 THEN COALESCE(SUM(rvc.vehicle_count), 0)
    END AS TotalVehicles
    , LPAD(rup.code, 2, '0') AS UKPortCode
    , LPAD(rfp.code, 2, '0') AS ForeignPortCode
    , rs.comments AS Comments
    , IF(rs.data_entry_method = 'advanced', 'bulk', rs.data_entry_method) AS DataEntryMethod
FROM roro_survey rs
    JOIN roro_operator ro ON rs.operator_id = ro.id
    JOIN route r ON r.id = rs.route_id
    JOIN route_foreign_port rfp ON rfp.id = r.foreign_port_id
    JOIN route_uk_port rup ON rup.id = r.uk_port_id
    LEFT JOIN roro_vehicle_count rvc ON rs.id = rvc.survey_id
WHERE rs.state IN ('closed', 'approved')
GROUP BY rs.id, rs.survey_period_start, rs.survey_period_start, rs.survey_period_start, rup.code, rfp.code, ro.code, ro.code, rs.is_active_for_period, rs.state, rup.code, rfp.code, rs.comments, rs.state, rs.data_entry_method, rs.data_entry_method
ORDER BY rs.survey_period_start, ro.code;
