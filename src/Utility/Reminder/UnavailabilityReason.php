<?php

namespace App\Utility\Reminder;

use DateInterval;

class UnavailabilityReason
{
    public const NOT_IN_PROGRESS = 'not-in-progress';
    public const NO_EMAIL_ADDRESSES = 'no-email-addresses';
    public const NO_NOTIFY_TEMPLATE = 'no-notify-template';
    public const TOO_SOON = 'too-soon';

    public function __construct(protected string $reason, protected ?string $eventType = null, protected ?DateInterval $interval = null)
    {
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getEventType(): ?string
    {
        return $this->eventType;
    }

    public function getInterval(): ?DateInterval
    {
        return $this->interval;
    }
}