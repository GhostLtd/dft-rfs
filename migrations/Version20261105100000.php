<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Sms;

final class Version20261105100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Notify callbacks: notify_api_response.notify_id (backfilled from accepted responses) and problem_seen_at; notify_callback table; SMS cap refusals relabelled "refused"';
    }

    // DDL commits implicitly; each statement is guarded instead, so that a re-run only does what is missing
    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $responses = $schema->getTable('notify_api_response');

        if (!$responses->hasColumn('notify_id')) {
            $this->addSql('ALTER TABLE notify_api_response ADD notify_id CHAR(36) DEFAULT NULL');
        }

        if (!$responses->hasColumn('problem_seen_at')) {
            $this->addSql('ALTER TABLE notify_api_response ADD problem_seen_at DATETIME DEFAULT NULL');
        }

        if (!$responses->hasIndex('idx_notify_api_response_notify_id')) {
            $this->addSql('CREATE INDEX idx_notify_api_response_notify_id ON notify_api_response (notify_id)');
        }

        if (!$schema->hasTable('notify_callback')) {
            $this->addSql(<<<SQL
                CREATE TABLE notify_callback (
                    id CHAR(36) NOT NULL,
                    response_id CHAR(36) NOT NULL,
                    notify_id CHAR(36) NOT NULL,
                    type VARCHAR(32) NOT NULL,
                    status VARCHAR(32) NOT NULL,
                    payload JSON NOT NULL,
                    occurred_at DATETIME DEFAULT NULL,
                    received_at DATETIME NOT NULL,
                    INDEX IDX_6E6CF2E4FBF32840 (response_id),
                    UNIQUE INDEX uniq_notify_callback_notify_id_type_status (notify_id, type, status),
                    PRIMARY KEY (id),
                    CONSTRAINT FK_6E6CF2E4FBF32840 FOREIGN KEY (response_id) REFERENCES notify_api_response (id) ON DELETE CASCADE
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL);
        }

        // Accepted responses hold Notify's send response, whose "id" is what callbacks refer to. Returned letters
        // arrive weeks after sending, so letters sent before this release need it too. Only fills what is missing.
        $this->addSql(<<<SQL
            UPDATE notify_api_response
               SET notify_id = JSON_UNQUOTE(JSON_EXTRACT(data, '$.id'))
             WHERE notify_id IS NULL
               AND JSON_UNQUOTE(JSON_EXTRACT(data, '$."symfony_messenger_bus.govuk_notify_status"')) = 'accepted'
               AND JSON_TYPE(JSON_EXTRACT(data, '$.id')) = 'STRING'
        SQL);

        // SMS refused by our own caps were recorded "failed"; they're now "refused" (not a delivery problem). Matched
        // on the refusal messages SmsAllowanceMiddleware has always used. Only relabels what is still "failed".
        $this->addSql(<<<SQL
            UPDATE notify_api_response
               SET data = JSON_SET(data, '$."symfony_messenger_bus.govuk_notify_status"', 'refused')
             WHERE message_class = ?
               AND JSON_UNQUOTE(JSON_EXTRACT(data, '$."symfony_messenger_bus.govuk_notify_status"')) = 'failed'
               AND (JSON_UNQUOTE(JSON_EXTRACT(data, '$.errors[0].error')) = 'SMS per-number daily cap reached'
                    OR JSON_UNQUOTE(JSON_EXTRACT(data, '$.errors[0].error')) LIKE 'SMS allowance for FY % exhausted')
        SQL, [Sms::class]);
    }

    public function down(Schema $schema): void
    {
        // "refused" didn't exist before this release
        $this->addSql(<<<SQL
            UPDATE notify_api_response
               SET data = JSON_SET(data, '$."symfony_messenger_bus.govuk_notify_status"', 'failed')
             WHERE JSON_UNQUOTE(JSON_EXTRACT(data, '$."symfony_messenger_bus.govuk_notify_status"')) = 'refused'
        SQL);

        if ($schema->hasTable('notify_callback')) {
            $this->addSql('DROP TABLE notify_callback');
        }

        $responses = $schema->getTable('notify_api_response');

        if ($responses->hasIndex('idx_notify_api_response_notify_id')) {
            $this->addSql('DROP INDEX idx_notify_api_response_notify_id ON notify_api_response');
        }

        if ($responses->hasColumn('problem_seen_at')) {
            $this->addSql('ALTER TABLE notify_api_response DROP problem_seen_at');
        }

        if ($responses->hasColumn('notify_id')) {
            $this->addSql('ALTER TABLE notify_api_response DROP notify_id');
        }
    }
}
