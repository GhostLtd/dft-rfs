<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220406090558 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pre_enquiry DROP FOREIGN KEY FK_FD1E25C1979B1AD6');
        $this->addSql('DROP INDEX IDX_FD1E25C1979B1AD6 ON pre_enquiry');
        $this->addSql('ALTER TABLE pre_enquiry ADD company_name VARCHAR(255) NOT NULL, DROP company_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pre_enquiry ADD company_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', DROP company_name');
        $this->addSql('ALTER TABLE pre_enquiry ADD CONSTRAINT FK_FD1E25C1979B1AD6 FOREIGN KEY (company_id) REFERENCES international_company (id)');
        $this->addSql('CREATE INDEX IDX_FD1E25C1979B1AD6 ON pre_enquiry (company_id)');
    }
}
