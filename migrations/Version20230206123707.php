<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230206123707 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Fix: Make NotifyApiResponse's recipient_hash nullable";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE notify_api_response CHANGE recipient_hash recipient_hash VARCHAR(64) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE notify_api_response CHANGE recipient_hash recipient_hash VARCHAR(64) NOT NULL');
    }
}
