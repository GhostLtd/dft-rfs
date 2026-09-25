<?php

namespace App\Utility\Notification;

enum NotificationType: string
{
    case INVITE = 'invite';
    case REMINDER_1 = 'reminder-1';
    case REMINDER_2 = 'reminder-2';
    case MANUAL_REMINDER = 'reminder-manual';
    case IN_PROGRESS_REMINDER_1 = 'in-progress-reminder-1';
    case IN_PROGRESS_REMINDER_2 = 'in-progress-reminder-2';
    case IN_PROGRESS_REMINDER_3 = 'in-progress-reminder-3';

    /** Admin-panel label */
    public function label(): string
    {
        return match ($this) {
            self::INVITE => 'Initial invitation',
            self::REMINDER_1 => 'Reminder 1',
            self::REMINDER_2 => 'Reminder 2',
            self::MANUAL_REMINDER => 'Manual reminder',
            self::IN_PROGRESS_REMINDER_1 => 'In-progress reminder 1',
            self::IN_PROGRESS_REMINDER_2 => 'In-progress reminder 2',
            self::IN_PROGRESS_REMINDER_3 => 'In-progress reminder 3',
        };
    }
}
