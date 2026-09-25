<?php

namespace App\Utility\Rehearsal;

use App\Utility\Notification\NotificationChannel;
use App\Utility\Notification\NotificationType;

/** A notification row a scenario expects the run to create. */
final class ExpectedRow
{
    public function __construct(
        public readonly NotificationType $type,
        public readonly NotificationChannel $channel,
    ) {}

    /**
     * Returns "{notification type}/{channel}" for comparing expected and recorded notifications.
     */
    public function key(): string
    {
        return "{$this->type->value}/{$this->channel->value}";
    }
}
