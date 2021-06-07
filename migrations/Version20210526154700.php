<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210526154700 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE pre_enquiry_note (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', pre_enquiry_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', note LONGTEXT NOT NULL, created_by VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_66AB05E2E4D0D43E (pre_enquiry_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE pre_enquiry_note ADD CONSTRAINT FK_66AB05E2E4D0D43E FOREIGN KEY (pre_enquiry_id) REFERENCES pre_enquiry (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE pre_enquiry_note');
    }
}
