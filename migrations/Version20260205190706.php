<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260205190706 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove created_by column from email_blacklist table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE email_blacklist DROP created_by
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE email_blacklist ADD created_by VARCHAR(255) DEFAULT NULL
        SQL);
    }
}
