<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210205143928 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE international_trip ADD is_swapped_trailer TINYINT(1) NOT NULL, ADD axle_configuration INT DEFAULT NULL, ADD is_changed_body_type TINYINT(1) DEFAULT NULL, ADD body_type VARCHAR(24) DEFAULT NULL, ADD gross_weight INT DEFAULT NULL, ADD carrying_capacity INT DEFAULT NULL, ADD trailer_configuration INT DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE international_trip DROP is_swapped_trailer, DROP axle_configuration, DROP is_changed_body_type, DROP body_type, DROP gross_weight, DROP carrying_capacity, DROP trailer_configuration');
    }
}
