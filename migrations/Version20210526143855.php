<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210526143855 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE international_pre_enquiry_response DROP FOREIGN KEY FK_D272F900E4D0D43E');
        $this->addSql('CREATE TABLE pre_enquiry (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', company_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', notified_date DATETIME DEFAULT NULL, first_reminder_sent_date DATETIME DEFAULT NULL, second_reminder_sent_date DATETIME DEFAULT NULL, notify_api_responses LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', response_start_date DATETIME DEFAULT NULL, submission_date DATETIME DEFAULT NULL, state VARCHAR(20) NOT NULL, invitation_email VARCHAR(255) DEFAULT NULL, invitation_address_line5 VARCHAR(255) DEFAULT NULL, invitation_address_line6 VARCHAR(255) DEFAULT NULL, invitation_address_line1 VARCHAR(255) DEFAULT NULL, invitation_address_line2 VARCHAR(255) DEFAULT NULL, invitation_address_line3 VARCHAR(255) DEFAULT NULL, invitation_address_line4 VARCHAR(255) DEFAULT NULL, invitation_address_postcode VARCHAR(10) DEFAULT NULL, INDEX IDX_FD1E25C1979B1AD6 (company_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE pre_enquiry_response (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', pre_enquiry_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', company_name VARCHAR(255) NOT NULL, correspondence_name VARCHAR(255) NOT NULL, phone VARCHAR(50) NOT NULL, email VARCHAR(255) NOT NULL, total_vehicle_count INT NOT NULL, international_journey_vehicle_count INT NOT NULL, employee_count INT NOT NULL, annual_journey_estimate INT NOT NULL, correspondence_address_line1 VARCHAR(255) DEFAULT NULL, correspondence_address_line2 VARCHAR(255) DEFAULT NULL, correspondence_address_line3 VARCHAR(255) DEFAULT NULL, correspondence_address_line4 VARCHAR(255) DEFAULT NULL, correspondence_address_postcode VARCHAR(10) DEFAULT NULL, UNIQUE INDEX UNIQ_907CD8F6E4D0D43E (pre_enquiry_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE pre_enquiry ADD CONSTRAINT FK_FD1E25C1979B1AD6 FOREIGN KEY (company_id) REFERENCES international_company (id)');
        $this->addSql('ALTER TABLE pre_enquiry_response ADD CONSTRAINT FK_907CD8F6E4D0D43E FOREIGN KEY (pre_enquiry_id) REFERENCES pre_enquiry (id)');
        $this->addSql('DROP TABLE international_pre_enquiry');
        $this->addSql('DROP TABLE international_pre_enquiry_response');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pre_enquiry_response DROP FOREIGN KEY FK_907CD8F6E4D0D43E');
        $this->addSql('CREATE TABLE international_pre_enquiry (id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', company_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', notified_date DATETIME DEFAULT NULL, first_reminder_sent_date DATETIME DEFAULT NULL, submission_date DATETIME DEFAULT NULL, second_reminder_sent_date DATETIME DEFAULT NULL, notify_api_responses LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:json)\', response_start_date DATETIME DEFAULT NULL, state VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, invitation_email VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, invitation_address_line5 VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, invitation_address_line6 VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, invitation_address_line1 VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, invitation_address_line2 VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, invitation_address_line3 VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, invitation_address_line4 VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, invitation_address_postcode VARCHAR(10) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_D2C673B979B1AD6 (company_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE international_pre_enquiry_response (id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', pre_enquiry_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', company_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, correspondence_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, phone VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, email VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, total_vehicle_count INT NOT NULL, international_journey_vehicle_count INT NOT NULL, employee_count INT NOT NULL, annual_journey_estimate INT NOT NULL, correspondence_address_line1 VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, correspondence_address_line2 VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, correspondence_address_line3 VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, correspondence_address_line4 VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, correspondence_address_postcode VARCHAR(10) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, UNIQUE INDEX UNIQ_D272F900E4D0D43E (pre_enquiry_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE international_pre_enquiry ADD CONSTRAINT FK_D2C673B979B1AD6 FOREIGN KEY (company_id) REFERENCES international_company (id)');
        $this->addSql('ALTER TABLE international_pre_enquiry_response ADD CONSTRAINT FK_D272F900E4D0D43E FOREIGN KEY (pre_enquiry_id) REFERENCES international_pre_enquiry (id)');
        $this->addSql('DROP TABLE pre_enquiry');
        $this->addSql('DROP TABLE pre_enquiry_response');
    }
}
