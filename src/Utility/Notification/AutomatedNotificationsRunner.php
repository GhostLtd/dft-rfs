<?php

namespace App\Utility\Notification;

use App\Utility\Reminder\AutomatedRemindersInterface;
use App\Utility\Reminder\DisabledRemindersHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Nightly entry point (app:cron:reminders:process and the /reminders/process cron route): runs every tagged
 * AutomatedRemindersInterface service (one per survey family), skipping families disabled by
 * APP_DISABLE_REMINDERS. Families run in no particular order; each depends only on its own tables. There is no
 * lock: the job runs once daily and each family's "not already sent" predicates mean a completed run is never
 * repeated.
 */
class AutomatedNotificationsRunner
{
    private const string LOG_PREFIX = 'Notifications: ';

    /** @param iterable<AutomatedRemindersInterface> $families */
    public function __construct(
        #[AutowireIterator('app.automated_reminders')]
        private readonly iterable $families,
        private readonly DisabledRemindersHelper $disabledRemindersHelper,
        private readonly LoggerInterface $logger,
    ) {}

    public function run(): void
    {
        foreach ($this->families as $reminders) {
            $family = $reminders->getFamily();

            if ($this->disabledRemindersHelper->isDisabled($family)) {
                $this->logger->info(self::LOG_PREFIX."{$family} disabled; skipping");
                continue;
            }

            $reminders->run();
        }
    }
}
