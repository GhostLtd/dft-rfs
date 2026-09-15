<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260910130000 extends AbstractMigration
{
    /** notificationTable => [newJoin, oldJoin, surveyTable] */
    private const array MAP = [
        'domestic_survey_notification' => ['domestic_survey_notification_notify_api_responses', 'domestic_survey_notify_api_responses', 'domestic_survey'],
        'international_survey_notification' => ['international_survey_notification_notify_api_responses', 'international_survey_notify_api_responses', 'international_survey'],
        'pre_enquiry_notification' => ['pre_enquiry_notification_notify_api_responses', 'pre_enquiry_notify_api_responses', 'pre_enquiry'],
    ];

    public function getDescription(): string
    {
        return 'Notifications: drop the legacy survey <-> notify_api_response join tables (superseded by the notification join tables)';
    }

    public function up(Schema $schema): void
    {
        foreach (self::MAP as [, $oldJoin]) {
            $this->addSql("DROP TABLE {$oldJoin}");
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::MAP as $notificationTable => [$newJoin, $oldJoin, $surveyTable]) {
            $this->addSql(<<<SQL
                CREATE TABLE {$oldJoin} (
                    survey_id CHAR(36) NOT NULL,
                    notify_api_response_id CHAR(36) NOT NULL,
                    INDEX IDX_{$oldJoin}_s (survey_id),
                    UNIQUE INDEX UNIQ_{$oldJoin}_r (notify_api_response_id),
                    PRIMARY KEY(survey_id, notify_api_response_id),
                    CONSTRAINT FK_{$oldJoin}_s FOREIGN KEY (survey_id) REFERENCES {$surveyTable} (id) ON DELETE CASCADE,
                    CONSTRAINT FK_{$oldJoin}_r FOREIGN KEY (notify_api_response_id) REFERENCES notify_api_response (id) ON DELETE CASCADE
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
                SQL);

            $this->addSql(<<<SQL
                INSERT INTO {$oldJoin} (survey_id, notify_api_response_id)
                SELECT n.survey_id, j.notify_api_response_id
                FROM {$newJoin} j JOIN {$notificationTable} n ON n.id = j.notification_id
            SQL);
        }
    }
}
