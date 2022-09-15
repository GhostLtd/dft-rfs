<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220719104326 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'IRHS LCNI';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE international_notification_interception (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', primary_name VARCHAR(255) NOT NULL, emails VARCHAR(1024) NOT NULL, UNIQUE INDEX UNIQ_2D0B093FF7B021CE (primary_name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE international_notification_interception_company_name (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', notification_interception_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', name VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_63370BEE5E237E06 (name), INDEX IDX_63370BEE479140CD (notification_interception_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE international_notification_interception_company_name ADD CONSTRAINT FK_63370BEE479140CD FOREIGN KEY (notification_interception_id) REFERENCES international_notification_interception (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE international_notification_interception_company_name DROP FOREIGN KEY FK_63370BEE479140CD');
        $this->addSql('DROP TABLE international_notification_interception');
        $this->addSql('DROP TABLE international_notification_interception_company_name');
    }
}
