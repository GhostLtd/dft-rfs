<?php

namespace App\Utility\Reminder;

use App\Entity\SurveyInterface;
use App\Entity\SurveyNotificationsInterface;
use App\Entity\SurveyStateInterface;
use App\Utility\Notification\NotificationQueryHelper;
use App\Utility\Notification\NotificationSender;
use App\Utility\Notification\NotificationType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;

/**
 * Selects first and second reminders for CSRGT, IRHS and pre-enquiry surveys.
 * Queues each survey's messages in its own transaction; subclasses define eligibility.
 */
abstract class AbstractSurveyReminders implements AutomatedRemindersInterface
{
    protected const string LOG_PREFIX = 'Notifications: ';
    protected const array NOT_STARTED = [SurveyStateInterface::STATE_NEW, SurveyStateInterface::STATE_INVITATION_SENT];
    private const string TOLERANCE = 'PT12H';

    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly NotificationQueryHelper $rows,
        private readonly NotificationSender $sender,
        private readonly ClockInterface $clock,
        private readonly LoggerInterface $logger,
    ) {}

    /** @return list<SurveyInterface&SurveyNotificationsInterface> */
    abstract public function dueForReminder1(\DateTimeImmutable $now): array;

    /** @return list<SurveyInterface&SurveyNotificationsInterface> */
    abstract public function dueForReminder2(\DateTimeImmutable $now): array;

    /**
     * Returns due surveys grouped by reminder type, without recording or queuing messages.
     */
    public function due(\DateTimeImmutable $now): DueReminders
    {
        $items = [];
        foreach ([[NotificationType::REMINDER_1, $this->dueForReminder1($now)], [NotificationType::REMINDER_2, $this->dueForReminder2($now)]] as [$type, $surveys]) {
            foreach ($surveys as $survey) {
                $items[] = new DueItem($type, [$survey]);
            }
        }

        return new DueReminders($this->getFamily(), [NotificationType::REMINDER_1, NotificationType::REMINDER_2], $items);
    }

    /**
     * Queues all due reminders, using one transaction per survey.
     */
    public function run(): void
    {
        $due = $this->due(\DateTimeImmutable::createFromInterface($this->clock->now()));
        foreach ($due->types as $type) {
            $this->queueReminders($type, $due->ofType($type));
        }
    }

    /**
     * Returns $now minus $delay, plus the 12-hour scheduling tolerance.
     */
    protected function cutoff(\DateTimeImmutable $now, string $delay): \DateTimeImmutable
    {
        return $now->sub(new \DateInterval($delay))->add(new \DateInterval(self::TOLERANCE));
    }

    /**
     * Queues each due survey's messages in its own transaction.
     *
     * @param list<DueItem> $items
     */
    private function queueReminders(NotificationType $type, array $items): void
    {
        $this->logger->info(self::LOG_PREFIX."{$type->value} for {$this->getFamily()}: ".count($items).' due');
        foreach ($items as $item) {
            $this->entityManager->wrapInTransaction(fn() => $this->sender->send($item->surveys[0], $type));
        }
    }
}
