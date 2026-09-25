<?php

namespace App\Utility\Rehearsal;

use Doctrine\DBAL\Connection;

/**
 * Checks that a database is ready for seeding: every operational table is empty.
 * Reference tables (routes, ports, countries, operators) are migration-created and not checked.
 */
class BaselineGuard
{
    private const array OPERATIONAL_TABLES = [
        'domestic_survey', 'domestic_survey_response', 'domestic_survey_note', 'domestic_survey_notification',
        'international_company', 'international_survey', 'international_survey_notification',
        'pre_enquiry', 'pre_enquiry_notification',
        'roro_survey', 'roro_survey_notification', 'roro_user',
        'passcode_user', 'notify_api_response', 'sms_attempt', 'sms_usage', 'sms_recipient_day',
        'messenger_messages', 'audit_log',
    ];

    public function __construct(private readonly Connection $connection) {}

    /**
     * Returns a description of each non-empty operational table, or an empty list when the database is ready for seeding.
     * A seeded database is never ready: the seed and its manifest are operational data.
     *
     * @return list<string>
     */
    public function problems(): array
    {
        $problems = [];
        foreach (self::OPERATIONAL_TABLES as $table) {
            $count = (int) $this->connection->fetchOne("SELECT COUNT(*) FROM {$table}");
            if ($count > 0) {
                $problems[] = "{$count} {$table} rows";
            }
        }
        return $problems;
    }
}
