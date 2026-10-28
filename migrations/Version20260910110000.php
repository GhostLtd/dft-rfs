<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Email;

final class Version20260910110000 extends AbstractMigration
{
    /** table => [notificationTable, responseJoinTable|null, columns: [column => type]] */
    private const array MAP = [
        'domestic_survey' => ['domestic_survey_notification', 'domestic_survey_notify_api_responses', [
            'invitation_sent_date' => 'invite',
            'first_reminder_sent_date' => 'reminder-1',
            'second_reminder_sent_date' => 'reminder-2',
            'latest_manual_reminder_sent_date' => 'reminder-manual',
        ]],
        'international_survey' => ['international_survey_notification', 'international_survey_notify_api_responses', [
            'invitation_sent_date' => 'invite',
            'first_reminder_sent_date' => 'reminder-1',
            'second_reminder_sent_date' => 'reminder-2',
            'latest_manual_reminder_sent_date' => 'reminder-manual',
        ]],
        'pre_enquiry' => ['pre_enquiry_notification', 'pre_enquiry_notify_api_responses', [
            'invitation_sent_date' => 'invite',
            'first_reminder_sent_date' => 'reminder-1',
            'second_reminder_sent_date' => 'reminder-2',
            'latest_manual_reminder_sent_date' => 'reminder-manual',
        ]],
        'roro_survey' => ['roro_survey_notification', null, [
            'first_reminder_sent_date' => 'reminder-1',
            'second_reminder_sent_date' => 'reminder-2',
        ]],
    ];

    public function getDescription(): string
    {
        return 'Notifications: back-fill notification rows from the legacy sent-date columns (dropped by Version20260910130000)';
    }

    public function up(Schema $schema): void
    {
        // Pull the data over to the new notifications tables
        foreach (self::MAP as $surveyTable => [$notificationTable, $joinTable, $columns]) {
            foreach ($columns as $column => $type) {
                if ($surveyTable === 'roro_survey') {
                    // RoRo reminder emails mention all of that operator's eligible surveys (i.e. the previous month,
                    // incomplete), and as such, the responses are logged against the RoroUser rather than a survey

                    // The sent dates are however logged against the individual surveys, and we need to copy those over
                    $this->addSql(<<<SQL
                        INSERT INTO roro_survey_notification (id, survey_id, type, channel, sent_at)
                        SELECT UUID(), s.id, '{$type}', 'email', s.{$column}
                        FROM roro_survey s
                        WHERE s.{$column} IS NOT NULL
                          AND NOT EXISTS (SELECT 1 FROM roro_survey_notification n WHERE n.survey_id = s.id AND n.type = '{$type}')
                    SQL);
                } else {
                    // Other surveys have one row per channel evidenced by the response log
                    $this->addSql(
                        <<<SQL
                        INSERT INTO {$notificationTable} (id, survey_id, type, channel, sent_at)
                        SELECT UUID(), s.id, '{$type}',
                               IF(r.message_class = :emailClass, 'email', 'letter'),
                               s.{$column}
                        FROM {$surveyTable} s
                        JOIN {$joinTable} j ON j.survey_id = s.id
                        JOIN notify_api_response r ON r.id = j.notify_api_response_id AND r.event_name = '{$type}'
                        WHERE s.{$column} IS NOT NULL
                          AND NOT EXISTS (SELECT 1 FROM {$notificationTable} n WHERE n.survey_id = s.id AND n.type = '{$type}')
                        GROUP BY s.id, r.message_class
                        SQL,
                        ['emailClass' => Email::class]
                    );

                    // A legacy date whose response log is missing (nothing in the join table for that survey and
                    // event) must not be lost when the column is dropped: record it on the channel the send would
                    // have used -- email for manual reminders (email-only), otherwise letter when the survey has an
                    // invitation address and email when it does not.
                    $fallbackChannel = $type === 'reminder-manual' ? "'email'" : "IF(s.invitation_address_line1 IS NOT NULL AND s.invitation_address_line1 <> '', 'letter', 'email')";
                    $this->addSql(<<<SQL
                        INSERT INTO {$notificationTable} (id, survey_id, type, channel, sent_at)
                        SELECT UUID(), s.id, '{$type}', {$fallbackChannel}, s.{$column}
                        FROM {$surveyTable} s
                        WHERE s.{$column} IS NOT NULL
                          AND NOT EXISTS (SELECT 1 FROM {$notificationTable} n WHERE n.survey_id = s.id AND n.type = '{$type}')
                    SQL);
                }
            }
        }
    }

    public function down(Schema $schema): void
    {
        // The legacy columns are restored (from these rows) by Version20260910130000's down()
        foreach (self::MAP as [$notificationTable]) {
            $this->addSql("DELETE FROM {$notificationTable}");
        }
    }
}
