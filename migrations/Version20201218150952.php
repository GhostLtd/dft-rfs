<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201218150952 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE domestic_survey CHANGE due_date survey_period_end DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE international_survey CHANGE due_date survey_period_end DATE DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE domestic_survey CHANGE survey_period_end due_date DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE international_survey CHANGE survey_period_end due_date DATE DEFAULT NULL');
    }
}
