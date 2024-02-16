<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240215132436 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Audit log: Increase size of category column';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE audit_log CHANGE category category VARCHAR(32) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE audit_log CHANGE category category VARCHAR(16) NOT NULL');
    }
}
