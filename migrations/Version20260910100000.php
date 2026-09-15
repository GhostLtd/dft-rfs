<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260910100000 extends AbstractMigration
{
    private const array TABLES = [
        'domestic_survey_notification' => ['domestic_survey', 'idx_domestic_survey_notification_'],
        'international_survey_notification' => ['international_survey', 'idx_international_survey_notification_'],
        'pre_enquiry_notification' => ['pre_enquiry', 'idx_pre_enquiry_notification_'],
        'roro_survey_notification' => ['roro_survey', 'idx_roro_survey_notification_'],
    ];

    public function getDescription(): string
    {
        return 'Notifications: create per-survey-type notification tables; widen notify_api_response.event_name to 32';
    }

    public function up(Schema $schema): void
    {
        foreach (self::TABLES as $table => [$surveyTable, $idx]) {
            $this->addSql(<<<SQL
                CREATE TABLE {$table} (
                    id CHAR(36) NOT NULL,
                    survey_id CHAR(36) NOT NULL,
                    type VARCHAR(32) NOT NULL,
                    channel VARCHAR(8) NOT NULL,
                    sent_at DATETIME NOT NULL,
                    INDEX {$idx}lookup (survey_id, type, sent_at),
                    PRIMARY KEY(id),
                    CONSTRAINT fk_{$table}_survey FOREIGN KEY (survey_id) REFERENCES {$surveyTable} (id) ON DELETE CASCADE
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL);
        }

        $this->addSql('ALTER TABLE notify_api_response CHANGE event_name event_name VARCHAR(32) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        foreach (array_keys(self::TABLES) as $table) {
            $this->addSql("DROP TABLE {$table}");
        }

        $this->addSql('ALTER TABLE notify_api_response CHANGE event_name event_name VARCHAR(16) NOT NULL');
    }
}
