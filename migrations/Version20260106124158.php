<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260106124158 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update view_international_firms: fix HGV column name, add LGV journey count';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
CREATE OR REPLACE VIEW view_international_firms AS (
    SELECT
        s.id                                        AS SurveyID
        , s.reference_number                        AS ReferenceNumber
        , s.survey_period_start                     AS SurveyPeriodStart
        , s.survey_period_end                       AS SurveyPeriodEnd
        , s.state                                   AS SurveyStatus
        , CASE
            WHEN s.second_reminder_sent_date IS NOT NULL THEN 2
            WHEN s.first_reminder_sent_date IS NOT NULL THEN 1
            ELSE 0
          END                                       AS NumberOfReminders
        , sr.annual_international_hgv_journey_count AS NumberOfInternationalHGVJourneys
        , sr.annual_international_lgv_journey_count AS NumberOfInternationalLGVJourneys
        , sr.activity_status                        AS ActivityStatus
        , s.reason_for_empty_survey                 AS ReasonForEmptySurvey
        , CASE
            WHEN s.reason_for_empty_survey = 'other' THEN s.reason_for_empty_survey_other
          END AS ReasonForEmptySurveyOther
        , sr.business_nature                        AS BusinessNature
        , sr.number_of_employees                    AS NumberOfEmployees
    FROM international_survey AS s
    LEFT JOIN international_survey_response AS sr ON s.id = sr.survey_id
)
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<SQL
CREATE OR REPLACE VIEW view_international_firms AS (
    SELECT
        s.id                                    AS SurveyID
        , s.reference_number                    AS ReferenceNumber
        , s.survey_period_start                 AS SurveyPeriodStart
        , s.survey_period_end                   AS SurveyPeriodEnd
        , s.state                               AS SurveyStatus
        , CASE
            WHEN s.second_reminder_sent_date IS NOT NULL THEN 2
            WHEN s.first_reminder_sent_date IS NOT NULL THEN 1
            ELSE 0
          END                                   AS NumberOfReminders
        , sr.annual_international_journey_count AS NumberOfInternationalJourneys
        , sr.activity_status                    AS ActivityStatus
        , s.reason_for_empty_survey             AS ReasonForEmptySurvey
        , CASE
            WHEN s.reason_for_empty_survey = 'other' THEN s.reason_for_empty_survey_other
          END AS ReasonForEmptySurveyOther
        , sr.business_nature                    AS BusinessNature
        , sr.number_of_employees                AS NumberOfEmployees
    FROM international_survey AS s
    LEFT JOIN international_survey_response AS sr ON s.id = sr.survey_id
)
SQL);
    }
}
