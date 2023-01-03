<?php

namespace App\Utility\Reminder;

use DateInterval;

class UnavailabilityReason
{
    public const NOT_IN_PROGRESS = 'not-in-progress';
    public const NO_EMAIL_ADDRESSES = 'no-email-addresses';
    public const NO_NOTIFY_TEMPLATE = 'no-notify-template';
    public const TOO_SOON = 'too-soon';

    protected string $reason;
    protected ?string $eventType;

    protected ?DateInterval $interval;

    public function __construct(string $reason, ?string $eventType = null, ?DateInterval $interval = null)
    {
        $this->reason = $reason;
        $this->eventType = $eventType;
        $this->interval = $interval;
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