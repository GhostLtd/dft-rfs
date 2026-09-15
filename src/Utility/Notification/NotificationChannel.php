<?php

namespace App\Utility\Notification;

enum NotificationChannel: string
{
    case LETTER = 'letter';
    case EMAIL = 'email';
    case SMS = 'sms';
}
