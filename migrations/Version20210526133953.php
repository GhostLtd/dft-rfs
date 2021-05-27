<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210526133953 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE blame_log');
        $this->addSql('ALTER TABLE international_pre_enquiry ADD second_reminder_sent_date DATETIME DEFAULT NULL, ADD notify_api_responses LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', ADD response_start_date DATETIME DEFAULT NULL, ADD state VARCHAR(20) NOT NULL, ADD invitation_email VARCHAR(255) DEFAULT NULL, ADD invitation_address_line5 VARCHAR(255) DEFAULT NULL, ADD invitation_address_line6 VARCHAR(255) DEFAULT NULL, ADD invitation_address_line1 VARCHAR(255) DEFAULT NULL, ADD invitation_address_line2 VARCHAR(255) DEFAULT NULL, ADD invitation_address_line3 VARCHAR(255) DEFAULT NULL, ADD invitation_address_line4 VARCHAR(255) DEFAULT NULL, ADD invitation_address_postcode VARCHAR(10) DEFAULT NULL, CHANGE due_date first_reminder_sent_date DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE blame_log (id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', class VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, description VARCHAR(1023) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, entity_id VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, user_id VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, date DATETIME NOT NULL, type VARCHAR(8) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, properties LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:json)\', associated_entity VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, associated_id VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE international_pre_enquiry ADD due_date DATETIME DEFAULT NULL, DROP first_reminder_sent_date, DROP second_reminder_sent_date, DROP notify_api_responses, DROP response_start_date, DROP state, DROP invitation_email, DROP invitation_address_line5, DROP invitation_address_line6, DROP invitation_address_line1, DROP invitation_address_line2, DROP invitation_address_line3, DROP invitation_address_line4, DROP invitation_address_postcode');
    }
}
