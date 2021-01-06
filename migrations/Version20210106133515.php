<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210106133515 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE blame_log (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', class VARCHAR(255) NOT NULL, description VARCHAR(1023) NOT NULL, entity_id VARCHAR(255) NOT NULL, user_id VARCHAR(255) NOT NULL, date DATETIME NOT NULL, type VARCHAR(8) NOT NULL, properties LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', associated_entity VARCHAR(255) DEFAULT NULL, associated_id VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE blame_log');
    }
}
