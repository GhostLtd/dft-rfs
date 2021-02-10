<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210210161535 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE international_survey ADD invitation_email VARCHAR(255) DEFAULT NULL, ADD invitation_address_line5 VARCHAR(255) DEFAULT NULL, ADD invitation_address_line6 VARCHAR(255) DEFAULT NULL, ADD invitation_address_line1 VARCHAR(255) DEFAULT NULL, ADD invitation_address_line2 VARCHAR(255) DEFAULT NULL, ADD invitation_address_line3 VARCHAR(255) DEFAULT NULL, ADD invitation_address_line4 VARCHAR(255) DEFAULT NULL, ADD invitation_address_postcode VARCHAR(10) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE international_survey DROP invitation_email, DROP invitation_address_line5, DROP invitation_address_line6, DROP invitation_address_line1, DROP invitation_address_line2, DROP invitation_address_line3, DROP invitation_address_line4, DROP invitation_address_postcode');
    }
}
