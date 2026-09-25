<?php

namespace App\Utility\Reminder;

use App\Utility\Notification\NotificationType;

/**
 * A due reminder for one survey, or one RoRo operator's group of surveys.
 */
final class DueItem
{
    /** @param non-empty-list<object> $surveys */
    public function __construct(
        public readonly NotificationType $type,
        public readonly array $surveys,
    ) {}
}
