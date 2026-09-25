<?php

namespace App\Utility\Reminder;

use App\Utility\Notification\NotificationType;

/**
 * A due survey postponed because it was contacted recently, with the contact date and event.
 */
final class HeldBack
{
    public function __construct(
        public readonly NotificationType $type,
        public readonly object $survey,
        public readonly ?\DateTime $latestContact,
        public readonly ?string $event,
    ) {}
}
