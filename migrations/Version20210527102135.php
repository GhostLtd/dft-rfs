<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210527102135 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE passcode_user ADD pre_enquiry_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE passcode_user ADD CONSTRAINT FK_21B8B82EE4D0D43E FOREIGN KEY (pre_enquiry_id) REFERENCES pre_enquiry (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_21B8B82EE4D0D43E ON passcode_user (pre_enquiry_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE passcode_user DROP FOREIGN KEY FK_21B8B82EE4D0D43E');
        $this->addSql('DROP INDEX UNIQ_21B8B82EE4D0D43E ON passcode_user');
        $this->addSql('ALTER TABLE passcode_user DROP pre_enquiry_id');
    }
}
