<?php

namespace App\Utility\Rehearsal;

use App\Utility\Notification\NotificationType;

/** One seeded situation and what the reminder commands should do with it. See ScenarioCatalogue. */
final class Scenario
{
    public const string CSRGT = 'csrgt';
    public const string IRHS = 'irhs';
    public const string PRE = 'pre';
    public const string RORO = 'roro';
    public const string IN_PROGRESS = 'ip';

    /**
     * @param list<ExpectedRow> $expect new rows the run should create; [] = nothing
     * @param list<array{0: NotificationType, 1: int}> $history seeded rows: type, days before seeding
     */
    public function __construct(
        public readonly string $name,
        public readonly string $family,
        public readonly ?string $state,
        public readonly array $expect,
        public readonly string $expectText,
        public readonly bool $invite = false,
        public readonly ?int $periodEndDays = null,
        public readonly ?int $periodEndAtFrom = null,
        public readonly array $history = [],
        public readonly bool $address = false,
        public readonly bool $invitationEmail = false,
        public readonly bool $contactEmail = false,
        public readonly ?string $telephone = null,
        public readonly ?int $chasedDaysAgo = null,
        public readonly ?string $registrationMark = null,
        public readonly ?string $reference = null,
    ) {}

    /**
     * Returns a unique sample email address for this scenario, using the non-deliverable example.invalid domain.
     */
    public function email(): string
    {
        return 'seed-'.str_replace(':', '-', $this->name).'@example.invalid';
    }

    /**
     * Returns the sample postal address heading identifying this scenario.
     */
    public function addressLine1(): string
    {
        return "SEED {$this->name}";
    }
}
