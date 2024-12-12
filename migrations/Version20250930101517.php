<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250930101517 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CSRGT export view';
    }

    public function up(Schema $schema): void
    {
        $this->addSql($this->getSqlForDomesticSurveyWithDates());
        $this->addSql($this->getSqlForDomesticDays());
        $this->addSql($this->getSqlForDomesticSummaryDays());
        $this->addSql($this->getSqlForDomesticStopDays());
        $this->addSql($this->getSqlForDomesticSurveyVehicles());
        $this->addSql($this->getSqlForDomesticSurveyExport());
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP VIEW IF EXISTS view_domestic_survey_eligible_with_dates');
        $this->addSql('DROP VIEW IF EXISTS view_domestic_days');
        $this->addSql('DROP VIEW IF EXISTS view_domestic_stop_days');
        $this->addSql('DROP VIEW IF EXISTS view_domestic_summary_days');
        $this->addSql('DROP VIEW IF EXISTS view_domestic_survey_vehicles');
        $this->addSql('DROP VIEW IF EXISTS view_export_domestic_survey');
    }

    public function getSqlForDomesticSurveyWithDates(): string
    {
        // Returns the contents of the domestic_survey table, but with CSRGT-flavoured year/week/quarter figures
        return <<<SQL
CREATE OR REPLACE VIEW view_domestic_survey_eligible_with_dates AS (
    WITH base_rows AS (
        SELECT 
            s.id
            , s.survey_period_start                                                                    AS period_start
            , DATE_SUB(s.survey_period_start, INTERVAL (DAYOFWEEK(s.survey_period_start) + 5) % 7 DAY) AS monday_of_date
            , YEAR(s.survey_period_start)                                                              AS calendar_year
        FROM domestic_survey s
        WHERE
            s.state IN ('new', 'invitation pending', 'invitation failed', 'invited', 'in-progress', 'closed', 'approved')
    )
    , year_boundaries AS (
        SELECT 
            b.id
            , b.period_start
            , b.monday_of_date
            , b.calendar_year
            , DATE(CONCAT(b.calendar_year, '-01-01'))     AS jan1_current
            , DATE(CONCAT(b.calendar_year + 1, '-01-01')) AS jan1_next
        FROM base_rows b
    )
    , aligned_year_starts AS (
        SELECT 
            yb.id
            , yb.period_start
            , yb.monday_of_date
            , yb.calendar_year
            , DATE_SUB(yb.jan1_current, INTERVAL (DAYOFWEEK(yb.jan1_current) + 5) % 7 DAY) AS start_of_current_weekyear
            , DATE_SUB(yb.jan1_next, INTERVAL (DAYOFWEEK(yb.jan1_next) + 5) % 7 DAY)       AS start_of_next_weekyear
        FROM year_boundaries yb
    )
    , weekyear_resolved AS (
        SELECT 
            ays.id
            , ays.period_start
            , ays.monday_of_date
            , CASE
                WHEN ays.monday_of_date >= ays.start_of_next_weekyear THEN ays.calendar_year + 1
                WHEN ays.monday_of_date < ays.start_of_current_weekyear THEN ays.calendar_year - 1
                ELSE ays.calendar_year
            END AS week_year
        FROM aligned_year_starts ays
    )
    , week_anchor AS (
        SELECT 
            wr.id
            , wr.period_start
            , wr.monday_of_date
            , wr.week_year
            , DATE_SUB(
                DATE(CONCAT(wr.week_year, '-01-01')),
                INTERVAL (DAYOFWEEK(DATE(CONCAT(wr.week_year, '-01-01'))) + 5) % 7 DAY
            ) AS start_of_week_year
        FROM weekyear_resolved wr
    )
    , week_numbers AS (
        SELECT 
            wa.id
            , wa.period_start
            , wa.week_year
            , 1 + FLOOR(DATEDIFF(wa.monday_of_date, wa.start_of_week_year) / 7) AS week_number
        FROM week_anchor wa
    )
    SELECT
        wn.week_year
        , wn.week_number
        , CASE
            WHEN wn.week_number BETWEEN 1 AND 13 THEN 1
            WHEN wn.week_number BETWEEN 14 AND 26 THEN 2
            WHEN wn.week_number BETWEEN 27 AND 39 THEN 3
            ELSE 4
        END AS quarter,
        s.*
    FROM base_rows AS b
    JOIN week_numbers AS wn ON wn.id = b.id
    JOIN domestic_survey AS s ON s.id = b.id
);
SQL;
    }

    public function getSqlForDomesticSurveyVehicles(): string
    {
        return <<<SQL
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
SQL;
    }

    public function getSqlForDomesticDays(): string
    {
        return <<<SQL
CREATE OR REPLACE VIEW view_domestic_days AS (
    SELECT
      s.id                      AS SurveyID
      , s.survey_period_start   AS SurveyPeriodStart
      , s.week_year             AS WeekBasedYear
      , s.week_number           AS WeekBasedWeek
      , s.quarter               AS WeekBasedQuarter
      , d.id                    AS DayID
      , d.number                AS DayNumber
      , CAST(DATE_FORMAT(
               DATE_ADD(s.survey_period_start, INTERVAL (d.number - 1) DAY),
               '%a'
        ) AS CHAR(3) CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci
                                AS DayOfWeek
    FROM view_domestic_survey_eligible_with_dates s
    JOIN domestic_survey_response r ON r.survey_id = s.id
    JOIN domestic_day d ON d.response_id = r.id
)
SQL;
    }

    public function getSqlForDomesticStopDays(): string
    {
        return <<<SQL
CREATE OR REPLACE VIEW view_domestic_stop_days AS (
    SELECT
        d.WeekBasedYear
        , d.WeekBasedWeek
        , d.WeekBasedQuarter          
        , d.DayNumber
        , d.SurveyID
        , ds.id                                                                                       AS JourneyID
        , ds.number                                                                                   AS RowNumber
        , ds.origin_location                                                                          AS Origin
        , ds.destination_location                                                                     AS Destination
        , ds.goods_description                                                                        AS TypeOfGoods
        , ds.goods_description_other                                                                  AS TypeOfGoodsOther
        , CASE
            WHEN ds.goods_description = 'empty' THEN 0
            WHEN ds.distance_travelled_unit = 'kilometres' THEN ds.distance_travelled_value
            WHEN ds.distance_travelled_unit = 'miles' THEN ds.distance_travelled_value * 1.609344
        END                                                                                           AS DistanceTravelledLoaded
        , CASE
            WHEN ds.goods_description != 'empty' THEN 0
            WHEN ds.distance_travelled_unit = 'kilometres' THEN ds.distance_travelled_value
            WHEN ds.distance_travelled_unit = 'miles' THEN ds.distance_travelled_value * 1.609344
        END                                                                                           AS DistanceTravelledEmpty
        , IF(ds.weight_of_goods_carried IS NULL, 0, ds.weight_of_goods_carried)                       AS WeightOfGoodsCarried

        # summary-only rows
        , CAST(NULL AS SIGNED)                                                                        AS WeightOfGoodsDelivered
        , CAST(NULL AS SIGNED)                                                                        AS WeightOfGoodsCollected
        , CAST(NULL AS SIGNED)                                                                        AS NumberOfStopsForDelivery
        , CAST(NULL AS SIGNED)                                                                        AS NumberOfStopsForCollection
        , CAST(NULL AS SIGNED)                                                                        AS NumberOfStopsForBoth
        , CAST(NULL AS CHAR(255)  CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci                   AS FurthestStop

        , IF(ds.cargo_type_code IS NULL AND ds.goods_description = 'empty', 'NS', ds.cargo_type_code) AS MOA
        , ds.border_crossing_location                                                                 AS WhereBorderCrossed
        , d.DayOfWeek
        , COALESCE(ds.was_limited_by_space, 0)                                                        AS WasLimitedBySpace
        , COALESCE(ds.was_limited_by_weight, 0)                                                       AS WasLimitedByWeight
        , IF(ds.hazardous_goods_code > 0, ds.hazardous_goods_code, NULL)                              AS DangerousGoodsCode
        , CASE goods_transferred_from
            WHEN 0 THEN NULL
            WHEN 1 THEN 'port'
            WHEN 2 THEN 'rail'
            WHEN 4 THEN 'air'
        END                                                                                           AS GoodsTransferredFrom
        , CASE goods_transferred_to
            WHEN 0 THEN NULL
            WHEN 1 THEN 'port'
            WHEN 2 THEN 'rail'
            WHEN 4 THEN 'air'
        END                                                                                           AS GoodsTransferredTo
        , CAST('stop' AS CHAR(7) CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci                    AS JourneyType
    FROM view_domestic_days d
    STRAIGHT_JOIN domestic_day_stop ds ON d.DayID = ds.day_id
)
SQL;
    }

    public function getSqlForDomesticSummaryDays(): string
    {
        return <<<SQL
CREATE OR REPLACE VIEW view_domestic_summary_days AS (
    SELECT
        d.WeekBasedYear
        , d.WeekBasedWeek
        , d.WeekBasedQuarter
        , d.DayNumber
        , d.SurveyID
        , ds.id                                                                                       AS JourneyID
        , CAST(NULL AS SIGNED)                                                                        AS RowNumber
        , ds.origin_location                                                                          AS Origin
        , ds.destination_location                                                                     AS Destination
        , ds.goods_description                                                                        AS TypeOfGoods
        , ds.goods_description_other                                                                  AS TypeOfGoodsOther
        , CASE
            WHEN ds.distance_travelled_loaded_unit = 'kilometres' THEN ds.distance_travelled_loaded_value
            WHEN ds.distance_travelled_loaded_unit = 'miles' THEN ds.distance_travelled_loaded_value * 1.609344
        END                                                                                           AS DistanceTravelledLoaded
        , CASE
            WHEN ds.distance_travelled_unloaded_unit = 'kilometres' THEN ds.distance_travelled_unloaded_value
            WHEN ds.distance_travelled_unloaded_unit = 'miles' THEN ds.distance_travelled_unloaded_value * 1.609344
        END                                                                                           AS DistanceTravelledEmpty
        , CAST(NULL AS SIGNED)                                                                        AS WeightOfGoodsCarried
        , IF(ds.weight_of_goods_unloaded IS NULL, 0, ds.weight_of_goods_unloaded)                     AS WeightOfGoodsDelivered
        , IF(ds.weight_of_goods_loaded IS NULL, 0, ds.weight_of_goods_loaded)                         AS WeightOfGoodsCollected
        , ds.number_of_stops_unloading                                                                AS NumberOfStopsForDelivery
        , ds.number_of_stops_loading                                                                  AS NumberOfStopsForCollection
        , ds.number_of_stops_loading_and_unloading                                                    AS NumberOfStopsForBoth
        , ds.furthest_stop                                                                            AS FurthestStop
        , IF(ds.cargo_type_code IS NULL AND ds.goods_description = 'empty', 'NS', ds.cargo_type_code) AS MOA
        , ds.border_crossing_location                                                                 AS WhereBorderCrossed
        , d.DayOfWeek
        , CAST(NULL AS SIGNED)                                                                        AS WasLimitedBySpace
        , CAST(NULL AS SIGNED)                                                                        AS WasLimitedByWeight
        , IF(ds.hazardous_goods_code > 0, ds.hazardous_goods_code, NULL)                              AS DangerousGoodsCode
        , CASE goods_transferred_from
            WHEN 0 THEN NULL
            WHEN 1 THEN 'port'
            WHEN 2 THEN 'rail'
            WHEN 4 THEN 'air'
        END                                                                                           AS GoodsTransferredFrom
        , CASE goods_transferred_to
            WHEN 0 THEN NULL
            WHEN 1 THEN 'port'
            WHEN 2 THEN 'rail'
            WHEN 4 THEN 'air'
        END                                                                                           AS GoodsTransferredTo
        , CAST('summary' AS CHAR(7) CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci                 AS JourneyType
    FROM view_domestic_days d
    STRAIGHT_JOIN domestic_day_summary ds ON d.DayID = ds.day_id
)
SQL;
    }

    public function getSqlForDomesticSurveyExport(): string
    {
        return <<<SQL
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
);
SQL;
    }
}
