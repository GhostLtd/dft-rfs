<?php

namespace App\Utility\Notification;

use App\Utility\Reminder\AutomatedRemindersInterface;
use App\Utility\Reminder\DisabledRemindersHelper;
use App\Utility\Reminder\DomesticInProgressReminders;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Runs the enabled reminder families for the 06:00 cron.
 * The 09:00 in-progress cron calls runOne() for its separate family.
 */
class AutomatedNotificationsRunner
{
    private const string LOG_PREFIX = 'Notifications: ';

    /** @param iterable<AutomatedRemindersInterface> $families */
    public function __construct(
        #[AutowireIterator('app.automated_reminders', exclude: [DomesticInProgressReminders::class])]
        private readonly iterable $families,
        private readonly DisabledRemindersHelper $disabledRemindersHelper,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Runs each configured family unless disabled by APP_DISABLE_REMINDERS.
     */
    public function run(): void
    {
        foreach ($this->families as $reminders) {
            $this->runOne($reminders);
        }
    }

    /**
     * Returns the 06:00 reminder families; in-progress reminders are excluded.
     *
     * @return iterable<AutomatedRemindersInterface>
     */
    public function getFamilies(): iterable
    {
        return $this->families;
    }

    /**
     * Runs one reminder family unless disabled by APP_DISABLE_REMINDERS.
     */
    public function runOne(AutomatedRemindersInterface $reminders): void
    {
        $family = $reminders->getFamily();

        if ($this->disabledRemindersHelper->isDisabled($family)) {
            $this->logger->info(self::LOG_PREFIX."{$family} disabled; skipping");
            return;
        }

        $reminders->run();
    }
}
