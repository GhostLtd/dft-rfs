<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230405085225 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'RoRo: Add survey note';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE roro_survey_note (id CHAR(36) NOT NULL, survey_id CHAR(36) NOT NULL, note LONGTEXT NOT NULL, created_by VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, was_chased TINYINT(1) NOT NULL, INDEX IDX_AC75AD9CB3FE509D (survey_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE roro_survey_note ADD CONSTRAINT FK_AC75AD9CB3FE509D FOREIGN KEY (survey_id) REFERENCES roro_survey (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE roro_survey_note DROP FOREIGN KEY FK_AC75AD9CB3FE509D');
        $this->addSql('DROP TABLE roro_survey_note');
    }
}
