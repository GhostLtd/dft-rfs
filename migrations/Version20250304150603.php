<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250304150603 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create combined LCNI';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE notification_interception (csrgt_emails VARCHAR(1024) DEFAULT NULL, irhs_emails VARCHAR(1024) DEFAULT NULL, dvla_emails VARCHAR(1024) DEFAULT NULL, id CHAR(36) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE notification_interception_company_name (name VARCHAR(255) NOT NULL, id CHAR(36) NOT NULL, notification_interception_id CHAR(36) NOT NULL, UNIQUE INDEX UNIQ_E1B2005C5E237E06 (name), INDEX IDX_E1B2005C479140CD (notification_interception_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE notification_interception_company_name ADD CONSTRAINT FK_E1B2005C479140CD FOREIGN KEY (notification_interception_id) REFERENCES notification_interception (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE notification_interception_company_name DROP FOREIGN KEY FK_E1B2005C479140CD');
        $this->addSql('DROP TABLE notification_interception');
        $this->addSql('DROP TABLE notification_interception_company_name');
    }
}
