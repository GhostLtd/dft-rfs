<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221020112741 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add record to survey notes for chasing haulier';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE domestic_survey_note ADD was_chased TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE international_survey_note ADD was_chased TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE pre_enquiry_note ADD was_chased TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE domestic_survey_note DROP was_chased');
        $this->addSql('ALTER TABLE international_survey_note DROP was_chased');
        $this->addSql('ALTER TABLE pre_enquiry_note DROP was_chased');
    }
}
