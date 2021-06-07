<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210602132921 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pre_enquiry_response ADD contact_name VARCHAR(255) NOT NULL, ADD contact_email VARCHAR(255) NOT NULL, ADD contact_address_line1 VARCHAR(255) DEFAULT NULL, ADD contact_address_line2 VARCHAR(255) DEFAULT NULL, ADD contact_address_line3 VARCHAR(255) DEFAULT NULL, ADD contact_address_line4 VARCHAR(255) DEFAULT NULL, DROP correspondence_name, DROP email, DROP correspondence_address_line1, DROP correspondence_address_line2, DROP correspondence_address_line3, DROP correspondence_address_line4, CHANGE phone contact_telephone VARCHAR(50) NOT NULL, CHANGE correspondence_address_postcode contact_address_postcode VARCHAR(10) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pre_enquiry_response ADD correspondence_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, ADD email VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, ADD correspondence_address_line1 VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD correspondence_address_line2 VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD correspondence_address_line3 VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD correspondence_address_line4 VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, DROP contact_name, DROP contact_email, DROP contact_address_line1, DROP contact_address_line2, DROP contact_address_line3, DROP contact_address_line4, CHANGE contact_telephone phone VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE contact_address_postcode correspondence_address_postcode VARCHAR(10) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
