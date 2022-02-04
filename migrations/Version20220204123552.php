<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\CurrencyOrPercentage;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220204123552 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'DriverAvailability: average_wage_increase and average_bonus field updates (currency field problems)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<EOQ
ALTER TABLE domestic_driver_availablity
    ADD average_wage_increase VARCHAR(255) DEFAULT NULL,
    ADD legacy_average_wage_increase_percentage INT DEFAULT NULL
EOQ);
        $this->addSql(<<<EOQ
UPDATE domestic_driver_availablity 
SET 
    average_wage_increase = average_wage_increase_value * 100 
WHERE 
    average_wage_increase_unit = :unit
EOQ, [
    'unit' => CurrencyOrPercentage::UNIT_CURRENCY
]);
        $this->addSql(<<<EOQ
UPDATE domestic_driver_availablity 
SET 
    legacy_average_wage_increase_percentage = average_wage_increase_value 
WHERE 
    average_wage_increase_unit = :unit
EOQ, [
            'unit' => CurrencyOrPercentage::UNIT_PERCENTAGE
        ]);
        $this->addSql('ALTER TABLE domestic_driver_availablity DROP average_wage_increase_unit, DROP average_wage_increase_value');

        $this->addSql('UPDATE domestic_driver_availablity SET average_bonus = average_bonus * 100 WHERE average_bonus IS NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<EOQ
ALTER TABLE domestic_driver_availablity  
    ADD average_wage_increase_unit VARCHAR(12) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, 
    ADD average_wage_increase_value INT DEFAULT NULL
EOQ);
        $this->addSql(<<<EOQ
UPDATE domestic_driver_availablity
SET 
    average_wage_increase_value = legacy_average_wage_increase_percentage, 
    average_wage_increase_unit = :unit
WHERE 
    legacy_average_wage_increase_percentage IS NOT NULL
EOQ, [
            'unit' => CurrencyOrPercentage::UNIT_PERCENTAGE
        ]);
        $this->addSql(<<<EOQ
UPDATE domestic_driver_availablity
SET 
    average_wage_increase_value = average_wage_increase / 100, 
    average_wage_increase_unit = :unit 
WHERE 
    average_wage_increase IS NOT NULL
EOQ, [
            'unit' => CurrencyOrPercentage::UNIT_CURRENCY
        ]);
        $this->addSql('ALTER TABLE domestic_driver_availablity DROP average_wage_increase, DROP legacy_average_wage_increase_percentage');

        $this->addSql('UPDATE domestic_driver_availablity SET average_bonus = average_bonus / 100 WHERE average_bonus IS NOT NULL');
    }
}
