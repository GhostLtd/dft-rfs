<?php

namespace App\Utility\Reminder;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * One survey family's automated reminders. Implementations are collected by tag into
 * AutomatedNotificationsRunner; adding a family means adding a class, nothing else. The runner makes no
 * promise about the order families run in -- each family's eligibility depends only on its own tables.
 */
#[AutoconfigureTag('app.automated_reminders')]
interface AutomatedRemindersInterface
{
    /** The APP_DISABLE_REMINDERS family name (a DisabledRemindersHelper constant). */
    public function getFamily(): string;

    /** Select what is due and send it, each survey (or group) in its own transaction. */
    public function run(): void;
}
