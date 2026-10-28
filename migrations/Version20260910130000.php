<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Email;

final class Version20260910130000 extends AbstractMigration
{
    /** guardPrefix => [surveyTable, notificationTable, [newJoin, oldJoin]|null, legacy columns: [column => type]] */
    private const array FAMILIES = [
        'domestic' => ['domestic_survey', 'domestic_survey_notification', ['domestic_survey_notification_notify_api_responses', 'domestic_survey_notify_api_responses'], [
            'invitation_sent_date' => 'invite',
            'first_reminder_sent_date' => 'reminder-1',
            'second_reminder_sent_date' => 'reminder-2',
            'latest_manual_reminder_sent_date' => 'reminder-manual',
        ]],
        'international' => ['international_survey', 'international_survey_notification', ['international_survey_notification_notify_api_responses', 'international_survey_notify_api_responses'], [
            'invitation_sent_date' => 'invite',
            'first_reminder_sent_date' => 'reminder-1',
            'second_reminder_sent_date' => 'reminder-2',
            'latest_manual_reminder_sent_date' => 'reminder-manual',
        ]],
        'pre_enquiry' => ['pre_enquiry', 'pre_enquiry_notification', ['pre_enquiry_notification_notify_api_responses', 'pre_enquiry_notify_api_responses'], [
            'invitation_sent_date' => 'invite',
            'first_reminder_sent_date' => 'reminder-1',
            'second_reminder_sent_date' => 'reminder-2',
            'latest_manual_reminder_sent_date' => 'reminder-manual',
        ]],
        'roro' => ['roro_survey', 'roro_survey_notification', null, [
            'first_reminder_sent_date' => 'reminder-1',
            'second_reminder_sent_date' => 'reminder-2',
        ]],
    ];

    private const string CHANNEL = "IF(r.message_class = :emailClass, 'email', 'letter')";

    public function getDescription(): string
    {
        return 'Notifications: verify the back-fills, then drop the legacy sent-date columns and the survey <-> notify_api_response join tables';
    }

    // DDL commits implicitly; each statement is guarded instead, so that a re-run only does what is left
    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $channel = self::CHANNEL;

        // Pass 1: every check, across all families, before anything is dropped. Each check is only made while its
        // source still exists, so that a run resumed part-way through pass 2 re-checks whatever is left.
        foreach (self::FAMILIES as $prefix => [$surveyTable, $notificationTable, $joins, $columns]) {
            if ($joins !== null && $schema->hasTable($joins[1])) {
                [$newJoin, $oldJoin] = $joins;

                // Each old link must have been carried over to a notification of the same survey, type and channel...
                $this->addGuard("{$prefix}_links_carried_over", <<<SQL
                    SELECT 1 FROM {$oldJoin} j
                    JOIN notify_api_response r ON r.id = j.notify_api_response_id
                    WHERE NOT EXISTS (
                        SELECT 1 FROM {$newJoin} x
                        JOIN {$notificationTable} n ON n.id = x.notification_id
                        WHERE x.notify_api_response_id = j.notify_api_response_id
                          AND n.survey_id = j.survey_id
                          AND n.type = r.event_name
                          AND n.channel = {$channel}
                    )
                    SQL, ['emailClass' => Email::class]);

                // ...and nothing else linked (the unique response column rules out a response being linked twice)
                $this->addGuard("{$prefix}_no_extra_links", <<<SQL
                    SELECT 1 FROM {$newJoin} x
                    WHERE NOT EXISTS (SELECT 1 FROM {$oldJoin} j WHERE j.notify_api_response_id = x.notify_api_response_id)
                    SQL);
            }

            foreach ($columns as $column => $type) {
                if (!$schema->getTable($surveyTable)->hasColumn($column)) {
                    continue;
                }

                // Each legacy date must be held by a row of its type, and every row of that type must hold its date
                $this->addGuard("{$prefix}_{$column}_preserved", <<<SQL
                    SELECT 1 FROM {$surveyTable} s
                    WHERE s.{$column} IS NOT NULL
                      AND NOT EXISTS (SELECT 1 FROM {$notificationTable} n WHERE n.survey_id = s.id AND n.type = '{$type}' AND n.sent_at = s.{$column})
                    UNION ALL
                    SELECT 1 FROM {$notificationTable} n
                    JOIN {$surveyTable} s ON s.id = n.survey_id
                    WHERE n.type = '{$type}' AND NOT (n.sent_at <=> s.{$column})
                    SQL);
            }
        }

        // Pass 2: the drops
        foreach (self::FAMILIES as [$surveyTable, , $joins, $columns]) {
            $existing = array_filter(array_keys($columns), fn(string $c) => $schema->getTable($surveyTable)->hasColumn($c));
            if ($existing) {
                $drops = join(', ', array_map(fn(string $c) => "DROP {$c}", $existing));
                $this->addSql("ALTER TABLE {$surveyTable} {$drops}");
            }

            if ($joins !== null && $schema->hasTable($joins[1])) {
                $this->addSql("DROP TABLE {$joins[1]}");
            }
        }
    }

    public function down(Schema $schema): void
    {
        // A rollback can die between the DDL and the data it restores, so the DDL is guarded, but the data
        // statements always run (and are idempotent).
        foreach (self::FAMILIES as [$surveyTable, $notificationTable, $joins, $columns]) {
            $missing = array_filter(array_keys($columns), fn(string $c) => !$schema->getTable($surveyTable)->hasColumn($c));
            if ($missing) {
                $adds = join(', ', array_map(fn(string $c) => "ADD {$c} DATETIME DEFAULT NULL", $missing));
                $this->addSql("ALTER TABLE {$surveyTable} {$adds}");
            }

            foreach ($columns as $column => $type) {
                $this->addSql(<<<SQL
                    UPDATE {$surveyTable} s
                    SET s.{$column} = (SELECT MAX(n.sent_at) FROM {$notificationTable} n WHERE n.survey_id = s.id AND n.type = '{$type}')
                SQL);
            }

            /** @var array{string, string}|null $joins */
            if ($joins === null) {
                continue;
            }

            [$newJoin, $oldJoin] = $joins;
            if (!$schema->hasTable($oldJoin)) {
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
            }

            $this->addSql(<<<SQL
                INSERT INTO {$oldJoin} (survey_id, notify_api_response_id)
                SELECT n.survey_id, x.notify_api_response_id
                FROM {$newJoin} x
                JOIN {$notificationTable} n ON n.id = x.notification_id
                WHERE NOT EXISTS (SELECT 1 FROM {$oldJoin} j WHERE j.notify_api_response_id = x.notify_api_response_id)
            SQL);
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
