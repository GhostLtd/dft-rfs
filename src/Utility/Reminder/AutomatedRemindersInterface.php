<?php

namespace App\Utility\Reminder;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * A reminder family selected by the automated runner and APP_DISABLE_REMINDERS.
 */
#[AutoconfigureTag('app.automated_reminders')]
interface AutomatedRemindersInterface
{
    /**
     * Returns the family name used in APP_DISABLE_REMINDERS.
     */
    public function getFamily(): string;

    /**
     * Selects due reminders and queues their messages, one transaction per survey or operator group.
     */
    public function run(): void;

    /**
     * Returns due reminders, held-back surveys and selection notes without queuing messages.
     */
    public function due(\DateTimeImmutable $now): DueReminders;
}
