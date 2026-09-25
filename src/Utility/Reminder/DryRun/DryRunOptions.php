<?php

namespace App\Utility\Reminder\DryRun;

final class DryRunOptions
{
    /**
     * @param list<string>|null $disabled null = the local APP_DISABLE_REMINDERS
     * @param ?string $from null = the family's configured APP_IN_PROGRESS_REMINDERS_FROM
     * @param ?int $smsCap null = the configured APP_SMS_ANNUAL_CAP
     * @param ?int $smsDailyCap null = the configured APP_SMS_DAILY_CAP_PER_NUMBER
     */
    public function __construct(
        public readonly \DateTimeImmutable $at,
        public readonly ?string $from,
        public readonly ?array $disabled,
        public readonly ?int $smsCap,
        public readonly ?int $smsDailyCap,
        public readonly ?int $limit,
        public readonly bool $summary,
    ) {}
}
