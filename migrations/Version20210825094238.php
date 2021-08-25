<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210825094238 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE domestic_driver_availablity (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', number_of_drivers_employed INT DEFAULT NULL, has_vacancies VARCHAR(20) DEFAULT NULL, number_of_driver_vacancies INT DEFAULT NULL, reasons_for_driver_vacancies LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', reasons_for_driver_vacancies_other VARCHAR(255) DEFAULT NULL, number_of_drivers_that_have_left INT DEFAULT NULL, have_wages_increased VARCHAR(20) DEFAULT NULL, reasons_for_wage_increase LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', reasons_for_wage_increase_other VARCHAR(255) DEFAULT NULL, has_paid_bonus VARCHAR(20) DEFAULT NULL, number_of_lorries_operated INT DEFAULT NULL, number_of_parked_lorries INT DEFAULT NULL, has_missed_deliveries VARCHAR(20) DEFAULT NULL, number_of_missed_deliveries INT DEFAULT NULL, average_wage_increase_value INT DEFAULT NULL, average_wage_increase_unit VARCHAR(12) DEFAULT NULL, average_bonus_value INT DEFAULT NULL, average_bonus_unit VARCHAR(12) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE domestic_survey ADD driver_availability_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE domestic_survey ADD CONSTRAINT FK_C3309B36B54804C FOREIGN KEY (driver_availability_id) REFERENCES domestic_driver_availablity (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C3309B36B54804C ON domestic_survey (driver_availability_id)');
        $this->addSql('ALTER TABLE domestic_survey_response ADD contact_business_name VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE domestic_survey DROP FOREIGN KEY FK_C3309B36B54804C');
        $this->addSql('DROP TABLE domestic_driver_availablity');
        $this->addSql('DROP INDEX UNIQ_C3309B36B54804C ON domestic_survey');
        $this->addSql('ALTER TABLE domestic_survey DROP driver_availability_id');
        $this->addSql('ALTER TABLE domestic_survey_response DROP contact_business_name');
    }
}
