<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210811094508 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'International: Move "reason for empty survey" fields from survey-response to survey';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE international_survey ADD reason_for_empty_survey VARCHAR(24) DEFAULT NULL, ADD reason_for_empty_survey_other VARCHAR(255) DEFAULT NULL');

        foreach(
            $this->connection->iterateAssociative("SELECT r.survey_id, r.reason_for_empty_survey, r.reason_for_empty_survey_other FROM international_survey_response r")
            as $result
        ) {
            $this->addSql('UPDATE international_survey s SET s.reason_for_empty_survey = :reason, s.reason_for_empty_survey_other = :other WHERE s.id = :id', [
                'id' => $result['survey_id'],
                'reason' => $result['reason_for_empty_survey'],
                'other' => $result['reason_for_empty_survey_other'],
            ]);
        }

        $this->addSql('ALTER TABLE international_survey_response DROP reason_for_empty_survey, DROP reason_for_empty_survey_other');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE international_survey_response ADD reason_for_empty_survey VARCHAR(24) DEFAULT NULL, ADD reason_for_empty_survey_other VARCHAR(255) DEFAULT NULL');

        foreach(
            $this->connection->iterateAssociative("SELECT s.id, s.reason_for_empty_survey, s.reason_for_empty_survey_other FROM international_survey s")
            as $result
        ) {
            $this->addSql('UPDATE international_survey_response r SET r.reason_for_empty_survey = :reason, r.reason_for_empty_survey_other = :other WHERE r.survey_id = :survey_id', [
                'survey_id' => $result['id'],
                'reason' => $result['reason_for_empty_survey'],
                'other' => $result['reason_for_empty_survey_other'],
            ]);
        }

        $this->addSql('ALTER TABLE international_survey DROP reason_for_empty_survey, DROP reason_for_empty_survey_other');
    }
}
