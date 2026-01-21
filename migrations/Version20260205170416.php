<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260205170416 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create email_blacklist table for global email blacklist feature';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE email_blacklist (email VARCHAR(255) NOT NULL, reason VARCHAR(500) DEFAULT NULL, created_at DATETIME NOT NULL, created_by VARCHAR(255) DEFAULT NULL, id CHAR(36) NOT NULL, UNIQUE INDEX UNIQ_341CCCAEE7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            DROP TABLE email_blacklist
        SQL);
    }
}
