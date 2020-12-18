<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201218150026 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE domestic_survey CHANGE dispatch_date notified_date DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE international_pre_enquiry CHANGE dispatch_date notified_date DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE international_survey CHANGE dispatch_date notified_date DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE domestic_survey CHANGE notified_date dispatch_date DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE international_pre_enquiry CHANGE notified_date dispatch_date DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE international_survey CHANGE notified_date dispatch_date DATETIME DEFAULT NULL');
    }
}
