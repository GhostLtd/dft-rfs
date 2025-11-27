<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251202144841 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'IRHS: rename annualInternationalJourneyCount -> annualInternationalHGVJourneyCount, add annualInternationalLGVJourneyCount';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE international_survey_response CHANGE annual_international_journey_count annual_international_hgv_journey_count INT DEFAULT NULL');
        $this->addSql('ALTER TABLE international_survey_response ADD annual_international_lgv_journey_count INT DEFAULT NULL');

    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE international_survey_response CHANGE annual_international_hgv_journey_count annual_international_journey_count INT DEFAULT NULL');
        $this->addSql('ALTER TABLE international_survey_response DROP annual_international_lgv_journey_count');
    }
}
