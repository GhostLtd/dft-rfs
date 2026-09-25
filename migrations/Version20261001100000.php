<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261001100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'SMS per-number daily cap: counter (sms_recipient_day) and the reservation day on the attempt ledger (sms_attempt.reserved_on)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            CREATE TABLE sms_recipient_day (
                recipient_hmac CHAR(64) NOT NULL,
                day DATE NOT NULL,
                sent INT DEFAULT 0 NOT NULL,
                PRIMARY KEY(recipient_hmac, day)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql('ALTER TABLE sms_attempt ADD reserved_on DATE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sms_attempt DROP reserved_on');
        $this->addSql('DROP TABLE sms_recipient_day');
    }
}
