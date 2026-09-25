<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260923100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CSRGT in-progress reminders: SMS allowance counter (sms_usage) and attempt ledger (sms_attempt)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            CREATE TABLE sms_usage (
                financial_year SMALLINT NOT NULL,
                sent INT DEFAULT 0 NOT NULL,
                PRIMARY KEY(financial_year)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<SQL
            CREATE TABLE sms_attempt (
                notification_id CHAR(36) NOT NULL,
                financial_year SMALLINT NOT NULL,
                attempts INT DEFAULT 0 NOT NULL,
                PRIMARY KEY(notification_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE sms_attempt');
        $this->addSql('DROP TABLE sms_usage');
    }
}
