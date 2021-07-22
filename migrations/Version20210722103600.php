<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210722103600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE domestic_survey ADD original_survey_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE domestic_survey ADD CONSTRAINT FK_C3309B36D96D2F88 FOREIGN KEY (original_survey_id) REFERENCES domestic_survey (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C3309B36D96D2F88 ON domestic_survey (original_survey_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE domestic_survey DROP FOREIGN KEY FK_C3309B36D96D2F88');
        $this->addSql('DROP INDEX UNIQ_C3309B36D96D2F88 ON domestic_survey');
        $this->addSql('ALTER TABLE domestic_survey DROP original_survey_id');
    }
}
