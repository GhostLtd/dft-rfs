<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\CurrencyOrPercentage;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210913104618 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE domestic_driver_availablity ADD exported_date DATETIME DEFAULT NULL');

        $this->addSql('ALTER TABLE domestic_driver_availablity ADD wage_increase_period VARCHAR(255) DEFAULT NULL, ADD wage_increase_period_other VARCHAR(255) DEFAULT NULL, ADD reasons_for_bonuses LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\'');

        $this->addSql('UPDATE domestic_driver_availablity SET average_bonus_value = null WHERE average_bonus_unit = :unit', [
            'unit' => CurrencyOrPercentage::UNIT_PERCENTAGE,
        ]);
        $this->addSql('ALTER TABLE domestic_driver_availablity DROP average_bonus_unit, CHANGE average_bonus_value average_bonus INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE domestic_driver_availablity ADD average_bonus_unit VARCHAR(12) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE average_bonus average_bonus_value INT DEFAULT NULL');
        $this->addSql('UPDATE domestic_driver_availablity SET average_bonus_unit = :unit WHERE average_bonus_value IS NOT NULL', [
            'unit' => CurrencyOrPercentage::UNIT_CURRENCY,
        ]);

        $this->addSql('ALTER TABLE domestic_driver_availablity DROP wage_increase_period, DROP wage_increase_period_other, DROP reasons_for_bonuses');

        $this->addSql('ALTER TABLE domestic_driver_availablity DROP exported_date');
    }
}
