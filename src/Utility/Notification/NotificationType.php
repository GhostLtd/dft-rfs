<?php

namespace App\Utility\Notification;

enum NotificationType: string
{
    case INVITE = 'invite';
    case REMINDER_1 = 'reminder-1';
    case REMINDER_2 = 'reminder-2';
    case MANUAL_REMINDER = 'reminder-manual';

    /** Admin-panel label */
    public function label(): string
    {
        return match ($this) {
            self::INVITE => 'Initial invitation',
            self::REMINDER_1 => 'Reminder 1',
            self::REMINDER_2 => 'Reminder 2',
            self::MANUAL_REMINDER => 'Manual reminder',
        };
    }
}
