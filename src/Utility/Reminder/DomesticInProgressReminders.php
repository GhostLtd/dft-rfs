<?php

namespace App\Utility\Reminder;

use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyNotification;
use App\Entity\SurveyStateInterface;
use App\Utility\Notification\NotificationQueryHelper;
use App\Utility\Notification\NotificationSender;
use App\Utility\Notification\NotificationType;
use App\Utility\Notification\SmsRecipientDailyCap;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;

/**
 * Selects and queues three CSRGT reminders for unfinished surveys after their period ends.
 * Reminder 1 waits 7 days; reminders 2 and 3 each wait 14 days after the previous reminder, with a 12-hour tolerance.
 * Only periods ending on or after APP_IN_PROGRESS_REMINDERS_FROM qualify. Recent contact delays sends for 7 days.
 * Runs on weekdays at 09:00, prunes old daily SMS counts, and uses one transaction per survey.
 */
class DomesticInProgressReminders implements AutomatedRemindersInterface
{
    private const string LOG_PREFIX = 'Notifications: ';
    private const string TOLERANCE = 'PT12H';
    private const string RECENT_CONTACT = 'P7D';

    private readonly ?\DateTime $remindersFrom;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly NotificationQueryHelper $rows,
        private readonly NotificationSender $sender,
        private readonly ManualReminderHelper $manualReminderHelper,
        private readonly SmsRecipientDailyCap $dailyCap,
        private readonly ClockInterface $clock,
        private readonly LoggerInterface $logger,
        private readonly ?string $remindersFromSetting = null,
    ) {
        $from = \DateTime::createFromFormat('!Y-m-d', $remindersFromSetting ?? '');
        $this->remindersFrom = ($from !== false && $from->format('Y-m-d') === $remindersFromSetting) ? $from : null;
    }

    public function getFamily(): string
    {
        return DisabledRemindersHelper::CSRGT_IN_PROGRESS;
    }

    /**
     * Prunes old SMS counts, then queues due reminders if the start date is valid and today is a weekday.
     */
    public function run(): void
    {
        $now = \DateTimeImmutable::createFromInterface($this->clock->now());

        // First, so it happens every day the cron runs, whatever the checks below decide.
        $pruned = $this->dailyCap->pruneBefore($now);
        if ($pruned > 0) {
            $this->logger->info(self::LOG_PREFIX."Pruned {$pruned} per-number SMS count(s) from before today");
        }

        if ($this->remindersFrom === null) {
            $this->logger->error(self::LOG_PREFIX."APP_IN_PROGRESS_REMINDERS_FROM unset or invalid: '{$this->remindersFromSetting}' — in-progress reminders not sent");
            return;
        }

        if ($this->isWeekend($now)) {
            $this->logger->info(self::LOG_PREFIX."{$this->getFamily()}: weekend, nothing sent");
            return;
        }

        $due = $this->due($now);
        foreach ($due->types as $type) {
            $this->queueReminders($type, $due->ofType($type));
        }
    }

    /**
     * Returns eligible reminders, recently contacted surveys held back, and any reason the run is disabled.
     */
    public function due(\DateTimeImmutable $now): DueReminders
    {
        $types = [NotificationType::IN_PROGRESS_REMINDER_1, NotificationType::IN_PROGRESS_REMINDER_2, NotificationType::IN_PROGRESS_REMINDER_3];

        if ($this->remindersFrom === null) {
            return new DueReminders($this->getFamily(), $types, [], [], ["APP_IN_PROGRESS_REMINDERS_FROM unset or invalid: '{$this->remindersFromSetting}'"]);
        }
        if ($this->isWeekend($now)) {
            return new DueReminders($this->getFamily(), $types, [], [], ['weekend (Europe/London): in-progress reminders are never sent on Saturdays or Sundays']);
        }

        $items = [];
        $heldBack = [];
        foreach ($this->candidates($now) as [$type, $surveys]) {
            foreach ($surveys as $survey) {
                [$latest, $event] = $this->manualReminderHelper->getMostRecentEventDate($survey);
                if ($this->contactedRecently($latest, $now)) {
                    $heldBack[] = new HeldBack($type, $survey, $latest, $event);
                } else {
                    $items[] = new DueItem($type, [$survey]);
                }
            }
        }

        return new DueReminders($this->getFamily(), $types, $items, $heldBack);
    }

    /**
     * Returns a copy using $setting as its start-date configuration for a dry run.
     */
    public function withRemindersFrom(?string $setting): self
    {
        return new self($this->entityManager, $this->rows, $this->sender, $this->manualReminderHelper, $this->dailyCap, $this->clock, $this->logger, $setting);
    }

    /**
     * Returns the configured APP_IN_PROGRESS_REMINDERS_FROM value, including invalid or unset values.
     */
    public function getRemindersFromSetting(): ?string
    {
        return $this->remindersFromSetting;
    }

    /**
     * Returns candidate surveys grouped by reminder type, before the recent-contact filter.
     *
     * @return list<array{0: NotificationType, 1: list<Survey>}>
     */
    private function candidates(\DateTimeImmutable $now): array
    {
        return [
            [NotificationType::IN_PROGRESS_REMINDER_1, $this->candidatesForReminder1($now)],
            [NotificationType::IN_PROGRESS_REMINDER_2, $this->candidatesAfter(NotificationType::IN_PROGRESS_REMINDER_1, NotificationType::IN_PROGRESS_REMINDER_2, $now)],
            [NotificationType::IN_PROGRESS_REMINDER_3, $this->candidatesAfter(NotificationType::IN_PROGRESS_REMINDER_2, NotificationType::IN_PROGRESS_REMINDER_3, $now)],
        ];
    }

    /**
     * Returns unfinished surveys past the first-reminder cutoff that have no reminder 1.
     *
     * @return list<Survey>
     */
    private function candidatesForReminder1(\DateTimeImmutable $now): array
    {
        if ($this->remindersFrom === null) {
            return [];
        }

        $qb = $this->queryInProgressSurveys()
            ->andWhere('s.surveyPeriodEnd <= :periodEndCutoff')->setParameter('periodEndCutoff', \DateTime::createFromImmutable($this->cutoff($now, 'P7D')))
            // Typed as a date: on SQLite, a datetime string would make a period ending on the day itself compare as earlier.
            ->andWhere('s.surveyPeriodEnd >= :remindersFrom')->setParameter('remindersFrom', $this->remindersFrom, Types::DATE_MUTABLE);
        $this->rows->withoutNotification($qb, 's', SurveyNotification::class, NotificationType::IN_PROGRESS_REMINDER_1);

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns unfinished surveys due after $previous, excluding those already given $type.
     *
     * @return list<Survey>
     */
    private function candidatesAfter(NotificationType $previous, NotificationType $type, \DateTimeImmutable $now): array
    {
        $qb = $this->queryInProgressSurveys();
        $this->rows->withoutNotification($qb, 's', SurveyNotification::class, $type);
        $this->rows->withLatestNotificationBy($qb, 's', SurveyNotification::class, $previous, $this->cutoff($now, 'P14D'));

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns a QueryBuilder restricted to surveys in the in-progress state.
     */
    private function queryInProgressSurveys(): QueryBuilder
    {
        return $this->entityManager->createQueryBuilder()->select('s')->from(Survey::class, 's')
            ->where('s.state = :inProgress')->setParameter('inProgress', SurveyStateInterface::STATE_IN_PROGRESS);
    }

    private function contactedRecently(?\DateTime $latest, \DateTimeImmutable $now): bool
    {
        return $latest !== null && $latest > $now->sub(new \DateInterval(self::RECENT_CONTACT));
    }

    /**
     * Returns $now minus $delay, plus the 12-hour scheduling tolerance.
     */
    private function cutoff(\DateTimeImmutable $now, string $delay): \DateTimeImmutable
    {
        return $now->sub(new \DateInterval($delay))->add(new \DateInterval(self::TOLERANCE));
    }

    private function isWeekend(\DateTimeImmutable $now): bool
    {
        return (int) $now->setTimezone(new \DateTimeZone('Europe/London'))->format('N') >= 6;
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
