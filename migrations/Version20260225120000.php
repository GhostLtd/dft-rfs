<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260225120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add SubmissionDate to domestic and international export views';
    }

    public function up(Schema $schema): void
    {
        // 1. view_domestic_survey_vehicles — add SubmissionDate after SurveyState
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
)
SQL);

        // 2. view_export_domestic_survey — recreate so MySQL registers the new column from v.*
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

        // 3. view_international_firms — add SubmissionDate after SurveyStatus
        $this->addSql(<<<SQL
CREATE OR REPLACE VIEW view_international_firms AS (
    SELECT
        s.id                                    AS SurveyID
        , s.reference_number                    AS ReferenceNumber
        , s.survey_period_start                 AS SurveyPeriodStart
        , s.survey_period_end                   AS SurveyPeriodEnd
        , s.state                               AS SurveyStatus
        , s.submission_date                     AS SubmissionDate
        , CASE
            WHEN s.second_reminder_sent_date IS NOT NULL THEN 2
            WHEN s.first_reminder_sent_date IS NOT NULL THEN 1
            ELSE 0
          END                                   AS NumberOfReminders
        , sr.annual_international_journey_count AS NumberOfInternationalJourneys
        , sr.activity_status                    AS ActivityStatus
        , s.reason_for_empty_survey             AS ReasonForEmptySurvey
        , CASE -- CASE statement makes sure this is NULL when not 'other'
            WHEN s.reason_for_empty_survey = 'other' THEN s.reason_for_empty_survey_other
          END AS ReasonForEmptySurveyOther
        , sr.business_nature                    AS BusinessNature
        , sr.number_of_employees                AS NumberOfEmployees
    FROM international_survey AS s
    LEFT JOIN international_survey_response AS sr ON s.id = sr.survey_id
)
SQL);

        // 4. view_export_international_survey — add f.SubmissionDate after f.SurveyStatus
        $this->addSql(<<<SQL
CREATE OR REPLACE VIEW view_export_international_survey AS (
SELECT
    f.SurveyID
    , f.ReferenceNumber
    , f.SurveyStatus
    , f.SubmissionDate
    , f.SurveyPeriodStart
    , f.SurveyPeriodEnd
    , f.NumberOfReminders
    , f.NumberOfInternationalJourneys
    , f.ActivityStatus
    , f.BusinessNature
    , f.NumberOfEmployees
    , f.ReasonForEmptySurvey
    , f.ReasonForEmptySurveyOther
    , t.TripID
    , t.RegMark
    , t.OperationType
    , t.Origin
    , t.Destination

    , t.OutboundDate
    , t.OutboundUkPort
    , t.OutboundForeignPort
    , t.OutboundWasEmpty
    , t.OutboundWasLimitedBySpace
    , t.OutboundWasLimitedByWeight

    , t.ReturnDate
    , t.ReturnUkPort
    , t.ReturnForeignPort
    , t.ReturnWasEmpty
    , t.ReturnWasLimitedBySpace
    , t.ReturnWasLimitedByWeight

    , t.GrossWeight
    , t.CarryingCapacity
    , t.BodyType
    , t.AxleConfiguration
    , t.CountriesTransitedCodes
    , t.CountriesTransitedOther

    , c.ConsignmentID
    , c.ActionType
    , c.MOA
    , c.TypeOfGoods
    , c.TypeOfGoodsOther
    , c.DangerousGoodsCode
    , c.WeightOfGoodsCarried
    , c.OriginPlace
    , c.OriginCountryCode
    , c.OriginCountryOther
    , c.LoadedOrder
    , c.DestinationPlace
    , c.DestinationCountryCode
    , c.DestinationCountryOther
    , c.UnloadedOrder
    , c.RowNumber
FROM view_international_firms AS f
LEFT JOIN view_international_trips AS t ON t.SurveyID = f.SurveyID
LEFT JOIN view_international_consignments AS c ON c.TripID = t.TripID
ORDER BY f.SurveyPeriodStart DESC, t.SurveyID, t.TripID, c.RowNumber
);
SQL);
    }

    public function down(Schema $schema): void
    {
    }
}
