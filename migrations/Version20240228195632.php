<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240228195632 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert to native JSON columns (part of Doctrine update)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE audit_log CHANGE data data JSON NOT NULL');
        $this->addSql('ALTER TABLE domestic_driver_availablity CHANGE reasons_for_driver_vacancies reasons_for_driver_vacancies JSON DEFAULT NULL, CHANGE reasons_for_wage_increase reasons_for_wage_increase JSON DEFAULT NULL, CHANGE reasons_for_bonuses reasons_for_bonuses JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE international_trip CHANGE countries_transitted countries_transitted JSON NOT NULL');
        $this->addSql('ALTER TABLE maintenance_lock CHANGE whitelisted_ips whitelisted_ips JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE notify_api_response CHANGE data data JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE audit_log CHANGE data data JSON NOT NULL');
        $this->addSql('ALTER TABLE domestic_driver_availablity CHANGE reasons_for_driver_vacancies reasons_for_driver_vacancies JSON DEFAULT NULL, CHANGE reasons_for_wage_increase reasons_for_wage_increase JSON DEFAULT NULL, CHANGE reasons_for_bonuses reasons_for_bonuses JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE international_trip CHANGE countries_transitted countries_transitted JSON NOT NULL');
        $this->addSql('ALTER TABLE maintenance_lock CHANGE whitelisted_ips whitelisted_ips JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE notify_api_response CHANGE data data JSON NOT NULL');
    }
}
