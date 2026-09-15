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
 * The execution machinery shared by the per-survey families (CSRGT, IRHS, pre-enquiry): find what is due for
 * reminder-1 then reminder-2, and send each survey through NotificationSender in its own transaction. The
 * eligibility itself -- what "due" means -- stays in each subclass's dueForReminder1()/dueForReminder2(), so a
 * family's conditions are read in one place rather than assembled from parameters. RoRo does not extend this
 * class: its per-operator digests and transactions are a different shape.
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

    public function run(): void
    {
        $now = \DateTimeImmutable::createFromInterface($this->clock->now());

        // reminder-1 first for readability only: the "own type not sent" and cutoff checks make the two loops
        // independent of each other's results within a run.
        $this->sendAll(NotificationType::REMINDER_1, $this->dueForReminder1($now));
        $this->sendAll(NotificationType::REMINDER_2, $this->dueForReminder2($now));
    }

    /**
     * "$delay ago", less the existing 12-hour tolerance, so a reminder due at 09:00 is not pushed to the next
     * day's run by a few hours' drift.
     */
    protected function cutoff(\DateTimeImmutable $now, string $delay): \DateTimeImmutable
    {
        return $now->sub(new \DateInterval($delay))->add(new \DateInterval(self::TOLERANCE));
    }

    /** @param list<SurveyInterface&SurveyNotificationsInterface> $due */
    private function sendAll(NotificationType $type, array $due): void
    {
        $this->logger->info(self::LOG_PREFIX."{$type->value} for {$this->getFamily()}: ".count($due).' due');
        foreach ($due as $survey) {
            $this->entityManager->wrapInTransaction(fn() => $this->sender->send($survey, $type));
        }
    }
}
