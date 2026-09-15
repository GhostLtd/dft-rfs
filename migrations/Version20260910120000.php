<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Email;

final class Version20260910120000 extends AbstractMigration
{
    /** notificationTable => [newJoin, oldJoin, surveyTable] */
    private const array MAP = [
        'domestic_survey_notification' => ['domestic_survey_notification_notify_api_responses', 'domestic_survey_notify_api_responses', 'domestic_survey'],
        'international_survey_notification' => ['international_survey_notification_notify_api_responses', 'international_survey_notify_api_responses', 'international_survey'],
        'pre_enquiry_notification' => ['pre_enquiry_notification_notify_api_responses', 'pre_enquiry_notify_api_responses', 'pre_enquiry'],
    ];

    /** newJoin => [notificationIndex, responseUniqueIndex] — Doctrine's auto-generated hashed index names */
    private const array INDEX_NAMES = [
        'domestic_survey_notification_notify_api_responses' => ['IDX_AC755B1EF1A9D84', 'UNIQ_AC755B1D6C71CEF'],
        'international_survey_notification_notify_api_responses' => ['IDX_D03512B4EF1A9D84', 'UNIQ_D03512B4D6C71CEF'],
        'pre_enquiry_notification_notify_api_responses' => ['IDX_C0F4DD14EF1A9D84', 'UNIQ_C0F4DD14D6C71CEF'],
    ];

    private const string CHANNEL_CASE = "CASE r.message_class WHEN :emailClass THEN 'email' ELSE 'letter' END";

    public function getDescription(): string
    {
        return 'Notifications: link notify_api_response rows to notification rows';
    }

    public function up(Schema $schema): void
    {
        $channelCase = self::CHANNEL_CASE;

        foreach (self::MAP as $notificationTable => [$newJoin, $oldJoin]) {
            [$notificationIndex, $responseUniqueIndex] = self::INDEX_NAMES[$newJoin];
            $this->addSql(<<<SQL
                CREATE TABLE {$newJoin} (
                    notification_id CHAR(36) NOT NULL,
                    notify_api_response_id CHAR(36) NOT NULL,
                    INDEX {$notificationIndex} (notification_id),
                    UNIQUE INDEX {$responseUniqueIndex} (notify_api_response_id),
                    PRIMARY KEY(notification_id, notify_api_response_id),
                    CONSTRAINT FK_{$newJoin}_n FOREIGN KEY (notification_id) REFERENCES {$notificationTable} (id) ON DELETE CASCADE,
                    CONSTRAINT FK_{$newJoin}_r FOREIGN KEY (notify_api_response_id) REFERENCES notify_api_response (id) ON DELETE CASCADE
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
                SQL);

            $this->addSql(
                <<<SQL
                INSERT INTO {$newJoin} (notification_id, notify_api_response_id)
                SELECT COALESCE(
                    (SELECT n.id FROM {$notificationTable} n
                      WHERE n.survey_id = j.survey_id AND n.type = r.event_name AND n.channel = ({$channelCase})
                        AND n.sent_at <= r.timestamp
                      ORDER BY n.sent_at DESC LIMIT 1),
                    (SELECT n.id FROM {$notificationTable} n
                      WHERE n.survey_id = j.survey_id AND n.type = r.event_name AND n.channel = ({$channelCase})
                      ORDER BY n.sent_at ASC LIMIT 1)
                ), r.id
                FROM {$oldJoin} j
                JOIN notify_api_response r ON r.id = j.notify_api_response_id
                SQL,
                ['emailClass' => Email::class]
            );
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::MAP as [$newJoin]) {
            $this->addSql("DROP TABLE {$newJoin}");
        }
    }
}
