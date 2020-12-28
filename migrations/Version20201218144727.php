<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201218144727 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE domestic_survey CHANGE start_date survey_period_start DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE international_survey CHANGE start_date survey_period_start DATE DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE domestic_survey CHANGE survey_period_start start_date DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE international_survey CHANGE survey_period_start start_date DATE DEFAULT NULL');
    }
}
