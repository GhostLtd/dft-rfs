<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210205095824 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE international_trip ADD is_swapped_trailer TINYINT(1) NOT NULL, ADD gross_weight INT DEFAULT NULL, ADD carrying_capacity INT DEFAULT NULL, ADD trailer_configuration INT DEFAULT NULL, ADD axle_configuration INT DEFAULT NULL, ADD body_type VARCHAR(24) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE international_trip DROP is_swapped_trailer, DROP gross_weight, DROP carrying_capacity, DROP trailer_configuration, DROP axle_configuration, DROP body_type');
    }
}
