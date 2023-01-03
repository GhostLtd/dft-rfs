<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230110132137 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Domestic: Add isExemptVehicleType';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE domestic_survey_response ADD is_exempt_vehicle_type TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE domestic_survey_response DROP is_exempt_vehicle_type');
    }
}