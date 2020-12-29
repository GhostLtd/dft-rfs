<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201229141937 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE action ADD trip_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE action ADD CONSTRAINT FK_47CC8C92A5BC2E0E FOREIGN KEY (trip_id) REFERENCES international_trip (id)');
        $this->addSql('CREATE INDEX IDX_47CC8C92A5BC2E0E ON action (trip_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE action DROP FOREIGN KEY FK_47CC8C92A5BC2E0E');
        $this->addSql('DROP INDEX IDX_47CC8C92A5BC2E0E ON action');
        $this->addSql('ALTER TABLE action DROP trip_id');
    }
}
