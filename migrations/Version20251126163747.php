<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251126163747 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'IRHS: Add activity_status_other, and make annual_international_journey_count nullable';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE international_survey_response ADD activity_status_other VARCHAR(255) DEFAULT NULL, CHANGE annual_international_journey_count annual_international_journey_count INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE international_survey_response DROP activity_status_other, CHANGE annual_international_journey_count annual_international_journey_count INT NOT NULL');
    }
}
