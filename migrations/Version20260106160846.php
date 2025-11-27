<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260106160846 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Pre-Enquiry: Rename vehicle columns to HGV, add LGV columns';
    }

    public function up(Schema $schema): void
    {
        // Rename existing columns to HGV
        $this->addSql('ALTER TABLE pre_enquiry_response CHANGE total_vehicle_count total_hgv_count INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pre_enquiry_response CHANGE international_journey_vehicle_count international_journey_hgv_count INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pre_enquiry_response CHANGE annual_journey_estimate annual_hgv_journey_estimate INT DEFAULT NULL');

        // Add LGV columns
        $this->addSql('ALTER TABLE pre_enquiry_response ADD total_lgv_count INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pre_enquiry_response ADD international_journey_lgv_count INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pre_enquiry_response ADD annual_lgv_journey_estimate INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Drop LGV columns
        $this->addSql('ALTER TABLE pre_enquiry_response DROP total_lgv_count');
        $this->addSql('ALTER TABLE pre_enquiry_response DROP international_journey_lgv_count');
        $this->addSql('ALTER TABLE pre_enquiry_response DROP annual_lgv_journey_estimate');

        // Rename HGV columns back to original names
        $this->addSql('ALTER TABLE pre_enquiry_response CHANGE total_hgv_count total_vehicle_count INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pre_enquiry_response CHANGE international_journey_hgv_count international_journey_vehicle_count INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pre_enquiry_response CHANGE annual_hgv_journey_estimate annual_journey_estimate INT DEFAULT NULL');
    }
}
