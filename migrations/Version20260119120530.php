<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260119120530 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add WeekNumber field to view_domestic_survey_vehicles';
    }

    public function up(Schema $schema): void
    {
        // Update view_domestic_survey_vehicles to add WeekNumber
        $this->addSql(<<<SQL
CREATE OR REPLACE VIEW view_domestic_survey_vehicles AS (
    SELECT
        s.survey_period_start                                        AS SurveyPeriodStart
        , 838 + FLOOR(DATEDIFF(s.survey_period_start, '2018-10-08') / 7) AS WeekNumber
        , s.week_year                                                AS WeekBasedYear
        , s.week_number                                              AS WeekBasedWeek
        , s.quarter                                                  AS WeekBasedQuarter
        , s.id                                                       AS SurveyID
        , s.state                                                    AS SurveyState
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
)
SQL);

        // Recreate view_export_domestic_survey to pick up new column from v.*
        $this->addSql(<<<SQL
CREATE OR REPLACE VIEW view_export_domestic_survey AS (
    WITH journeys AS (
        SELECT * FROM view_domestic_stop_days
        UNION ALL
        SELECT * FROM view_domestic_summary_days
    )
    SELECT
        v.*
        , CASE WHEN j.JourneyID IS NULL THEN 1 ELSE 0 END AS NotUsed
        , j.JourneyID
        , j.DayNumber
        , j.JourneyType
        , j.RowNumber
        , j.Origin
        , j.Destination
        , j.TypeOfGoods
        , j.TypeOfGoodsOther
        , j.DistanceTravelledLoaded
        , j.DistanceTravelledEmpty
        , j.WeightOfGoodsCarried
        , j.WeightOfGoodsDelivered
        , j.WeightOfGoodsCollected
        , j.NumberOfStopsForDelivery
        , j.NumberOfStopsForCollection
        , j.NumberOfStopsForBoth
        , j.FurthestStop
        , j.MOA
        , j.WhereBorderCrossed
        , j.DayOfWeek
        , j.WasLimitedBySpace
        , j.WasLimitedByWeight
        , j.DangerousGoodsCode
        , j.GoodsTransferredFrom
        , j.GoodsTransferredTo
    FROM view_domestic_survey_vehicles v
    LEFT JOIN journeys j ON j.SurveyID = v.SurveyID
)
SQL);
    }

    public function down(Schema $schema): void
    {
        // Restore view_domestic_survey_vehicles without WeekNumber
        $this->addSql(<<<SQL
CREATE OR REPLACE VIEW view_domestic_survey_vehicles AS (
    SELECT
        s.survey_period_start                                        AS SurveyPeriodStart
        , s.week_year                                                AS WeekBasedYear
        , s.week_number                                              AS WeekBasedWeek
        , s.quarter                                                  AS WeekBasedQuarter
        , s.id                                                       AS SurveyID
        , s.state                                                    AS SurveyState
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
)
SQL);

        // Recreate view_export_domestic_survey without WeekNumber
        $this->addSql(<<<SQL
CREATE OR REPLACE VIEW view_export_domestic_survey AS (
    WITH journeys AS (
        SELECT * FROM view_domestic_stop_days
        UNION ALL
        SELECT * FROM view_domestic_summary_days
    )
    SELECT
        v.*
        , CASE WHEN j.JourneyID IS NULL THEN 1 ELSE 0 END AS NotUsed
        , j.JourneyID
        , j.DayNumber
        , j.JourneyType
        , j.RowNumber
        , j.Origin
        , j.Destination
        , j.TypeOfGoods
        , j.TypeOfGoodsOther
        , j.DistanceTravelledLoaded
        , j.DistanceTravelledEmpty
        , j.WeightOfGoodsCarried
        , j.WeightOfGoodsDelivered
        , j.WeightOfGoodsCollected
        , j.NumberOfStopsForDelivery
        , j.NumberOfStopsForCollection
        , j.NumberOfStopsForBoth
        , j.FurthestStop
        , j.MOA
        , j.WhereBorderCrossed
        , j.DayOfWeek
        , j.WasLimitedBySpace
        , j.WasLimitedByWeight
        , j.DangerousGoodsCode
        , j.GoodsTransferredFrom
        , j.GoodsTransferredTo
    FROM view_domestic_survey_vehicles v
    LEFT JOIN journeys j ON j.SurveyID = v.SurveyID
)
SQL);
    }
}
