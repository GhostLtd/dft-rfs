<?php

namespace App\Utility\Notification;

enum NotificationChannel: string
{
    case LETTER = 'letter';
    case EMAIL = 'email';
    case SMS = 'sms';

    public function label(): string
    {
        return match ($this) {
            self::LETTER => 'Letter',
            self::EMAIL => 'Email',
            self::SMS => 'SMS',
        };
    }
}
