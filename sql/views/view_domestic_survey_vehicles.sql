-- @depends view_domestic_survey_eligible_with_dates

CREATE OR REPLACE VIEW view_domestic_survey_vehicles AS (
    SELECT
        s.survey_period_start                                        AS SurveyPeriodStart
        , 838 + FLOOR(DATEDIFF(s.survey_period_start, '2018-10-08') / 7) AS WeekNumber
        , s.week_year                                                AS WeekBasedYear
        , s.week_number                                              AS WeekBasedWeek
        , s.quarter                                                  AS WeekBasedQuarter
        , s.id                                                       AS SurveyID
        , s.state                                                    AS SurveyState
        , s.submission_date                                          AS SubmissionDate
        , s.registration_mark                                        AS RegistrationMark
        , v.carrying_capacity                                        AS CarryingCapacity
        , v.gross_weight                                             AS GrossWeight
        , v.operation_type                                           AS OperationType
        , v.body_type                                                AS BodyType
        , s.is_northern_ireland                                      AS IsNorthernIreland
        , CASE
            WHEN v.fuel_type IS NULL THEN NULL
            WHEN v.is_fuel_type_hybrid = 1 THEN CONCAT(REPLACE(v.fuel_type, '_', '-'), '-hybrid')
            ELSE REPLACE(v.fuel_type, '_', '-')
          END                                                        AS FuelType
        , v.fuel_quantity                                            AS FuelQuantity
        , v.fuel_quantity_other                                      AS FuelQuantityOther
        , r.business_nature                                          AS BusinessType
        , s.invitation_address_postcode                              AS SurveyPostcode
        , r.number_of_employees                                      AS EmployeeCount
        , r.reason_for_empty_survey                                  AS ReasonForEmptySurvey
        , r.reason_for_empty_survey_other                            AS ReasonForEmptySurveyOther
        , s.invitation_address_line1                                 AS RegisteredKeeper
        , r.is_in_possession_of_vehicle                              AS PossessionState
        , r.unable_to_complete_date                                  AS ScrappedStolenDate
        , v.trailer_configuration                                    AS TrailerConfigurationCode
        , v.axle_configuration                                       AS AxleConfigurationCode
    FROM view_domestic_survey_eligible_with_dates s
    LEFT JOIN domestic_survey_response r ON r.survey_id = s.id
    LEFT JOIN domestic_vehicle v ON r.vehicle_id = v.id
);
