<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251106134129 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add extra indexes to help export query';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_day_response_number ON domestic_day (response_id, number, id)');
        $this->addSql('CREATE INDEX idx_day_stop_number ON domestic_day_stop (day_id, number, id)');
        $this->addSql('CREATE INDEX idx_summary_day ON domestic_day_summary (day_id, id)');
        $this->addSql('CREATE INDEX idx_survey_state ON domestic_survey (state, id)');
        $this->addSql('CREATE INDEX idx_survey_start_id ON domestic_survey (survey_period_start, id)');
        $this->addSql('CREATE INDEX idx_dsr_survey_vehicle ON domestic_survey_response (survey_id, vehicle_id, id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_day_response_number ON domestic_day');
        $this->addSql('DROP INDEX idx_day_stop_number ON domestic_day_stop');
        $this->addSql('DROP INDEX idx_summary_day ON domestic_day_summary');
        $this->addSql('DROP INDEX idx_survey_state ON domestic_survey');
        $this->addSql('DROP INDEX idx_survey_start_id ON domestic_survey');
        $this->addSql('DROP INDEX idx_dsr_survey_vehicle ON domestic_survey_response');
    }
}
