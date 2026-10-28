<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Email;

final class Version20260910120000 extends AbstractMigration
{
    /** notificationTable => [newJoin, oldJoin, guardPrefix] */
    private const array MAP = [
        'domestic_survey_notification' => ['domestic_survey_notification_notify_api_responses', 'domestic_survey_notify_api_responses', 'domestic'],
        'international_survey_notification' => ['international_survey_notification_notify_api_responses', 'international_survey_notify_api_responses', 'international'],
        'pre_enquiry_notification' => ['pre_enquiry_notification_notify_api_responses', 'pre_enquiry_notify_api_responses', 'pre_enquiry'],
    ];

    public function getDescription(): string
    {
        return 'Notifications: link notify_api_response rows to notification rows (tables created by Version20260910100000)';
    }

    public function up(Schema $schema): void
    {
        foreach (self::MAP as $notificationTable => [$newJoin, $oldJoin, $guardPrefix]) {
            // Version20260910110000 creates at most one row per survey/type/channel, so each response has exactly
            // one candidate notification. The tables have no such constraint (later sends add history), so check.
            $this->addGuard(
                "{$guardPrefix}_single_notification_candidate",
                "SELECT 1 FROM {$notificationTable} GROUP BY survey_id, type, channel HAVING COUNT(*) > 1"
            );

            // Anti-join on the unique response column, so that a re-run only adds missing links
            $this->addSql(
                <<<SQL
                INSERT INTO {$newJoin} (notification_id, notify_api_response_id)
                SELECT n.id, r.id
                FROM {$oldJoin} j
                JOIN notify_api_response r ON r.id = j.notify_api_response_id
                JOIN {$notificationTable} n
                  ON n.survey_id = j.survey_id
                 AND n.type = r.event_name
                 AND n.channel = IF(r.message_class = :emailClass, 'email', 'letter')
                WHERE NOT EXISTS (SELECT 1 FROM {$newJoin} x WHERE x.notify_api_response_id = r.id)
                SQL,
                ['emailClass' => Email::class]
            );
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::MAP as [$newJoin]) {
            $this->addSql("DELETE FROM {$newJoin}");
        }
    }

    /**
     * Queues a check that fails the migration (MySQL error 3819, naming the guard) if $offendingRowsSql returns
     * any rows. Being queued rather than run from PHP, it previews correctly under --dry-run, when the tables
     * created earlier in this sequence don't yet exist.
     */
    private function addGuard(string $name, string $offendingRowsSql, array $params = []): void
    {
        // A failed guard leaves its (session-scoped) table behind
        $this->addSql('DROP TEMPORARY TABLE IF EXISTS migration_guard');
        $this->addSql("CREATE TEMPORARY TABLE migration_guard (ok TINYINT NOT NULL, CONSTRAINT {$name} CHECK (ok = 1))");
        $this->addSql("INSERT INTO migration_guard (ok) SELECT NOT EXISTS ({$offendingRowsSql})", $params);
        $this->addSql('DROP TEMPORARY TABLE migration_guard');
    }
}
