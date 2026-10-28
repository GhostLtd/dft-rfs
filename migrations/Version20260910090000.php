<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260910090000 extends AbstractMigration
{
    /** Every table that references notify_api_response before the notifications revamp */
    private const array OWNER_JOINS = [
        'domestic_survey_notify_api_responses',
        'international_survey_notify_api_responses',
        'pre_enquiry_notify_api_responses',
        'roro_user_notify_api_responses',
    ];

    public function getDescription(): string
    {
        return 'Notifications: delete notify_api_response rows orphaned by survey / RoRo user deletion';
    }

    // A single DELETE is atomic, so a re-run simply deletes whatever is left
    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        // A database that already has the revamped notification tables has had its survey-keyed joins dropped,
        // and orphans can no longer be identified against them
        $this->skipIf(
            !$schema->hasTable('domestic_survey_notify_api_responses'),
            'Survey-keyed notify_api_response joins are gone (notifications revamp already applied)'
        );

        // Survey (and RoRo user) deletion only cascades to the join row, leaving the response itself behind.
        // The timestamp guard protects a response that has just been persisted ahead of its join row.
        $notLinked = join("\n  AND ", array_map(
            fn(string $join) => "NOT EXISTS (SELECT 1 FROM {$join} j WHERE j.notify_api_response_id = r.id)",
            self::OWNER_JOINS
        ));

        $this->addSql(<<<SQL
            DELETE r FROM notify_api_response r
            WHERE {$notLinked}
              AND r.timestamp < NOW() - INTERVAL 1 DAY
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Deleted orphaned notify_api_response rows can only be recovered from a backup');
    }
}
