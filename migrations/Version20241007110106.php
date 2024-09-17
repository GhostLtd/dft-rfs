<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241007110106 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'fuel quantity/type changes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE domestic_vehicle ADD fuel_type VARCHAR(20) DEFAULT NULL, ADD fuel_quantity_other LONGTEXT DEFAULT NULL, ADD is_fuel_type_hybrid TINYINT(1) DEFAULT NULL');
        $this->addSql('UPDATE domestic_vehicle SET fuel_type = "other", fuel_quantity_other = CONCAT("Unknown: ", fuel_quantity_value, " ", fuel_quantity_unit) WHERE fuel_quantity_value is not null');
        $this->addSql('ALTER TABLE domestic_vehicle DROP fuel_quantity_unit, CHANGE fuel_quantity_value fuel_quantity NUMERIC(8, 2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE domestic_vehicle ADD fuel_quantity_unit VARCHAR(8) DEFAULT NULL, CHANGE fuel_quantity fuel_quantity_value NUMERIC(8, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE domestic_vehicle DROP fuel_type, DROP fuel_quantity_other, DROP is_fuel_type_hybrid');
    }
}
