<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210427135737 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE domestic_survey_note (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', survey_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', note LONGTEXT NOT NULL, created_by VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_1204C091B3FE509D (survey_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE international_survey_note (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', survey_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', note LONGTEXT NOT NULL, created_by VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_F966B825B3FE509D (survey_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE domestic_survey_note ADD CONSTRAINT FK_1204C091B3FE509D FOREIGN KEY (survey_id) REFERENCES domestic_survey (id)');
        $this->addSql('ALTER TABLE international_survey_note ADD CONSTRAINT FK_F966B825B3FE509D FOREIGN KEY (survey_id) REFERENCES international_survey (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE domestic_survey_note');
        $this->addSql('DROP TABLE international_survey_note');
    }
}
