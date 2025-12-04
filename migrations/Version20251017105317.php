<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251017105317 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Driver availability export view';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
CREATE OR REPLACE VIEW view_export_domestic_driver_availability AS (
    SELECT
        sv.registration_mark                            AS RegistrationMark
        , sv.submission_date                            AS SubmissionDate
        , YEAR(sv.submission_date)                      AS SubmissionYear
        , MONTH(sv.submission_date)                     AS SubmissionMonth
        , da.id                                         AS DriverAvailabilityID
        , da.number_of_drivers_employed                 AS NumberOfDriversEmployed
        , da.has_vacancies                              AS HasVacancies
        , da.number_of_driver_vacancies                 AS NumberOfDriverVacancies
        , CASE
            -- Sometimes they choose "yes", but then don't choose reasons, so we end up with []
            WHEN JSON_LENGTH(reasons_for_driver_vacancies) = 0 THEN NULL 
            ELSE da.reasons_for_driver_vacancies
          END                                           AS ReasonsForDriverVacancies
        , da.reasons_for_driver_vacancies_other         AS ReasonsForDriverVacanciesOther
        , da.number_of_drivers_that_have_left           AS NumberOfDriversThatHaveLeft
        , da.have_wages_increased                       AS HaveWagesIncreased
        , CASE
              WHEN JSON_LENGTH(reasons_for_wage_increase) = 0 THEN NULL
              ELSE da.reasons_for_wage_increase
          END                                           AS ReasonsForWageIncrease
        , da.reasons_for_wage_increase_other            AS ReasonsForWageIncreaseOther
        , da.has_paid_bonus                             AS HasPaidBonus
        , CASE
              WHEN JSON_LENGTH(reasons_for_bonuses) = 0 THEN NULL
              ELSE da.reasons_for_bonuses
          END                                           AS ReasonsForBonuses
        , da.average_bonus                              AS AverageBonus
        , da.number_of_lorries_operated                 AS NumberOfLorriesOperated
        , da.number_of_parked_lorries                   AS NumberOfParkedLorries
        , da.has_missed_deliveries                      AS HasMissedDeliveries
        , da.number_of_missed_deliveries                AS NumberOfMissedDeliveries
        , da.wage_increase_period                       AS WageIncreasePeriod
        , da.wage_increase_period_other                 AS WageIncreasePeriodOther
        , da.average_wage_increase                      AS AverageWageIncrease
        -- Always NULL / not used:
        -- , da.legacy_average_wage_increase_percentage    AS LegacyAverageWageIncreasePercentage
    FROM domestic_driver_availablity da
    JOIN view_domestic_survey_eligible_with_dates sv ON sv.driver_availability_id = da.id
    WHERE sv.state IN ('approved', 'closed')
    ORDER BY sv.survey_period_start
)
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP VIEW IF EXISTS view_export_domestic_driver_availability');
    }
}
