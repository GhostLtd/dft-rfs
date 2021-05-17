<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210504100855 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE domestic_survey ADD first_reminder_sent_date DATETIME DEFAULT NULL, ADD second_reminder_sent_date DATETIME DEFAULT NULL, ADD notify_api_responses LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE international_survey ADD first_reminder_sent_date DATETIME DEFAULT NULL, ADD second_reminder_sent_date DATETIME DEFAULT NULL, ADD notify_api_responses LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\'');

        // set reminder dates (used for reminder status) to not null.
        $this->addSql("UPDATE domestic_survey SET first_reminder_sent_date = '1970-01-01', second_reminder_sent_date = '1970-01-01'");
        $this->addSql("UPDATE international_survey SET first_reminder_sent_date = '1970-01-01', second_reminder_sent_date = '1970-01-01'");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE domestic_survey DROP first_reminder_sent_date, DROP second_reminder_sent_date, DROP notify_api_responses');
        $this->addSql('ALTER TABLE international_survey DROP first_reminder_sent_date, DROP second_reminder_sent_date, DROP notify_api_responses');
    }
}
