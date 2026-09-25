<?php

namespace App\Utility\Reminder;

use App\Entity\RoRo\Survey;
use App\Entity\RoRo\SurveyNotification;
use App\Entity\SurveyStateInterface;
use App\Utility\Notification\NotificationChannel;
use App\Utility\Notification\NotificationQueryHelper;
use App\Utility\Notification\NotificationRecorder;
use App\Utility\Notification\NotificationTemplates;
use App\Utility\Notification\NotificationType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Email;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Queues digest emails for unfinished surveys from the previous London calendar month.
 * Only active operators with users and active, assigned routes qualify.
 * Reminder 1 is sent once; reminder 2 waits 7 days after the latest reminder 1, with a 12-hour tolerance.
 * Each operator group uses one transaction. Messages and Notify responses belong to operator users.
 */
class RoRoReminders implements AutomatedRemindersInterface
{
    private const string LOG_PREFIX = 'Notifications: ';
    private const array NOT_COMPLETED = [SurveyStateInterface::STATE_NEW, SurveyStateInterface::STATE_IN_PROGRESS];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly NotificationQueryHelper $rows,
        private readonly NotificationRecorder $recorder,
        private readonly MessageBusInterface $messageBus,
        private readonly TranslatorInterface $translator,
        private readonly LoginLinkHandlerInterface $roroLoginLinkHandler,
        private readonly ClockInterface $clock,
        private readonly LoggerInterface $logger,
    ) {}

    public function getFamily(): string
    {
        return DisabledRemindersHelper::RORO;
    }

    /**
     * Returns due surveys grouped by operator and reminder type, without queuing messages.
     */
    public function due(\DateTimeImmutable $now): DueReminders
    {
        $items = [];
        foreach ([[NotificationType::REMINDER_1, $this->dueForReminder1($now)], [NotificationType::REMINDER_2, $this->dueForReminder2($now)]] as [$type, $surveys]) {
            foreach ($this->groupByOperator($surveys) as $group) {
                $items[] = new DueItem($type, $group);
            }
        }

        return new DueReminders($this->getFamily(), [NotificationType::REMINDER_1, NotificationType::REMINDER_2], $items);
    }

    public function run(): void
    {
        $now = \DateTimeImmutable::createFromInterface($this->clock->now());
        $due = $this->due($now);
        foreach ($due->types as $type) {
            $this->queueOperatorDigests($type, $due->ofType($type), $now);
        }
    }

    /**
     * Queues one digest per operator user, using one transaction per operator.
     *
     * @param list<DueItem> $items one per operator
     */
    private function queueOperatorDigests(NotificationType $type, array $items, \DateTimeImmutable $now): void
    {
        $this->logger->info(self::LOG_PREFIX."{$type->value} for {$this->getFamily()}: ".array_sum(array_map(fn(DueItem $i) => count($i->surveys), $items)).' due');

        foreach ($items as $item) {
            $this->entityManager->wrapInTransaction(fn() => $this->queueDigest($item->surveys, $type, $now));
        }
    }

    /** @return list<Survey> */
    public function dueForReminder1(\DateTimeImmutable $now): array
    {
        $qb = $this->queryLastMonthSurveys($now);
        $this->rows->withoutNotification($qb, 'survey', SurveyNotification::class, NotificationType::REMINDER_1);

        return $this->filterActiveAssignments($qb->getQuery()->getResult());
    }

    /** @return list<Survey> */
    public function dueForReminder2(\DateTimeImmutable $now): array
    {
        $cutoff = $now->sub(new \DateInterval('P7D'))->add(new \DateInterval('PT12H'));

        $qb = $this->queryLastMonthSurveys($now);
        $this->rows->withoutNotification($qb, 'survey', SurveyNotification::class, NotificationType::REMINDER_2);
        $this->rows->withLatestNotificationBy($qb, 'survey', SurveyNotification::class, NotificationType::REMINDER_1, $cutoff);

        return $this->filterActiveAssignments($qb->getQuery()->getResult());
    }

    /**
     * Returns a query for unfinished surveys from last month, whose operators have users.
     */
    private function queryLastMonthSurveys(\DateTimeImmutable $now): QueryBuilder
    {
        $london = \DateTime::createFromImmutable($now->setTimezone(new \DateTimeZone('Europe/London')));
        $lastMonth = (clone $london)->modify('first day of last month midnight');
        $thisMonth = (clone $london)->modify('first day of this month midnight');

        return $this->entityManager->createQueryBuilder()
            ->select('survey, operator, user, route, ukPort, foreignPort')
            ->from(Survey::class, 'survey')
            ->join('survey.operator', 'operator')
            ->join('operator.users', 'user')
            ->join('survey.route', 'route')
            ->join('route.ukPort', 'ukPort')
            ->join('route.foreignPort', 'foreignPort')
            ->where('survey.state IN (:notCompleted)')->setParameter('notCompleted', self::NOT_COMPLETED)
            // N.B. If you pass times in addition to dates, this doesn't work (at least for sqlite)
            ->andWhere('survey.surveyPeriodStart >= :lastMonth')->setParameter('lastMonth', $lastMonth->format('Y-m-d'))
            ->andWhere('survey.surveyPeriodStart < :thisMonth')->setParameter('thisMonth', $thisMonth->format('Y-m-d'));
    }

    /**
     * Returns surveys whose operator and route are active and whose route is still assigned.
     *
     * @param list<Survey> $surveys
     * @return list<Survey>
     */
    private function filterActiveAssignments(array $surveys): array
    {
        return array_values(array_filter($surveys, function (Survey $survey): bool {
            $operator = $survey->getOperator();
            $route = $survey->getRoute();

            return $operator->isIsActive()
                && $route->getIsActive()
                // A route can be unassigned from an Operator, but the surveys still exist.
                && $operator->hasRoute($route);
        }));
    }

    /**
     * Returns surveys grouped by operator ID.
     *
     * @param list<Survey> $surveys
     * @return array<string, list<Survey>>
     */
    private function groupByOperator(array $surveys): array
    {
        $grouped = [];
        foreach ($surveys as $survey) {
            $grouped[(string) $survey->getOperator()->getId()][] = $survey;
        }
        return $grouped;
    }

    /**
     * Records one notification per survey and queues digest emails for its operator's users.
     * Runs within the caller's transaction.
     *
     * @param non-empty-list<Survey> $surveys
     */
    private function queueDigest(array $surveys, NotificationType $type, \DateTimeImmutable $now): void
    {
        foreach ($surveys as $survey) {
            $this->recorder->record($survey, $type, NotificationChannel::EMAIL, \DateTime::createFromImmutable($now));
        }

        $emails = $this->buildDigest($surveys, $type);
        foreach ($emails as $email) {
            $this->messageBus->dispatch($email);
        }

        $operator = $surveys[0]->getOperator();
        $first = $emails[0] ?? null;
        $this->logger->info(self::LOG_PREFIX."Sending reminders for '{$operator->getName()}': "
            .'route_names=['.str_replace("\n", '; ', $first?->getPersonalisation()['route_names'] ?? '').'], '
            .'year='.($first?->getPersonalisation()['year'] ?? '').', month='.($first?->getPersonalisation()['month'] ?? '')
            .', users=['.implode(', ', array_map(fn(Email $e) => $e->getEmailAddress(), $emails)).']');
    }

    /**
     * Returns one Email message per operator user, listing the group's outstanding routes.
     * All surveys must belong to the same operator. Returns an empty list if the operator has no users.
     * Does not record notifications or queue messages.
     *
     * @param non-empty-list<Survey> $surveys
     * @return list<Email>
     */
    public function buildDigest(array $surveys, NotificationType $type): array
    {
        $operator = $surveys[0]->getOperator();
        $template = NotificationTemplates::for(Survey::class, $type)[NotificationChannel::EMAIL->value];

        $routeNamesByRouteId = [];
        $mostRecentSurveyYear = null;
        $mostRecentSurveyMonth = null;

        foreach ($surveys as $survey) {
            $route = $survey->getRoute();
            $routeNamesByRouteId[$route->getId()] ??= $this->translator->trans('roro.route', [
                'ukPort' => $route->getUkPort()->getName(),
                'foreignPort' => $route->getForeignPort()->getName(),
            ]);

            // Every survey in this group belongs to the same calendar month.
            $start = $survey->getSurveyPeriodStart();
            $mostRecentSurveyYear = max($mostRecentSurveyYear, (int) $start->format('Y'));
            $mostRecentSurveyMonth = max($mostRecentSurveyMonth, (int) $start->format('n'));
        }

        $routeNames = array_values($routeNamesByRouteId);
        sort($routeNames);
        $routeNamesForEmail = join("\n", array_map(fn($name) => "* {$name}", $routeNames));
        $formattedMonth = (new \DateTime("{$mostRecentSurveyYear}-{$mostRecentSurveyMonth}-1 00:00:00"))->format('F');

        $emails = [];
        foreach ($operator->getUsers() as $user) {
            $emails[] = new Email(
                $type->value,
                $user::class,
                $user->getId(),
                $user->getEmail(),
                $template,
                [
                    'operator' => (string) $operator->getName(),
                    'route_names' => $routeNamesForEmail,
                    'login_link' => $this->roroLoginLinkHandler->createLoginLink($user)->getUrl(),
                    'year' => (string) $mostRecentSurveyYear,
                    'month' => $formattedMonth,
                ],
            );
        }

        return $emails;
    }
}
