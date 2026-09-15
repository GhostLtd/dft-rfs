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
 * RoRo automated reminders: for every survey of the previous calendar month (Europe/London) that has not been
 * completed, on an active route still assigned to an active operator that has users, one digest email per
 * operator user listing that operator's outstanding routes.
 *  - reminder-1: once, as soon as the survey is in last month's window
 *  - reminder-2: 7 days after the latest reminder-1 (with the existing 12-hour tolerance)
 * Each operator's group -- its rows and its users' emails -- is one transaction. The emails originate from the
 * RoRoUser (not a notification row), so Notify responses attach to the user; hence this class records rows
 * through NotificationRecorder and dispatches itself rather than going through NotificationSender.
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

    public function run(): void
    {
        $now = \DateTimeImmutable::createFromInterface($this->clock->now());

        // reminder-1 first for readability only: the "own type not sent" and cutoff checks make the two loops
        // independent of each other's results within a run.
        $this->sendGroups(NotificationType::REMINDER_1, $this->dueForReminder1($now), $now);
        $this->sendGroups(NotificationType::REMINDER_2, $this->dueForReminder2($now), $now);
    }

    /** @param list<Survey> $due */
    private function sendGroups(NotificationType $type, array $due, \DateTimeImmutable $now): void
    {
        $this->logger->info(self::LOG_PREFIX."{$type->value} for {$this->getFamily()}: ".count($due).' due');

        foreach ($this->groupByOperator($due) as $surveys) {
            $this->entityManager->wrapInTransaction(fn() => $this->sendDigest($surveys, $type, $now));
        }
    }

    /** @return list<Survey> */
    public function dueForReminder1(\DateTimeImmutable $now): array
    {
        $qb = $this->lastMonthCandidates($now);
        $this->rows->none($qb, 'survey', SurveyNotification::class, NotificationType::REMINDER_1);

        return $this->acceptable($qb->getQuery()->getResult());
    }

    /** @return list<Survey> */
    public function dueForReminder2(\DateTimeImmutable $now): array
    {
        $cutoff = $now->sub(new \DateInterval('P7D'))->add(new \DateInterval('PT12H'));

        $qb = $this->lastMonthCandidates($now);
        $this->rows->none($qb, 'survey', SurveyNotification::class, NotificationType::REMINDER_2);
        $this->rows->latestAtOrBefore($qb, 'survey', SurveyNotification::class, NotificationType::REMINDER_1, $cutoff);

        return $this->acceptable($qb->getQuery()->getResult());
    }

    /** Not-completed surveys whose period starts in the previous calendar month, for operators that have users. */
    private function lastMonthCandidates(\DateTimeImmutable $now): QueryBuilder
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

    /** @param list<Survey> $surveys @return list<Survey> active operator, active route, route still assigned to the operator */
    private function acceptable(array $surveys): array
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

    /** @param list<Survey> $surveys @return array<string, list<Survey>> operator id => surveys */
    private function groupByOperator(array $surveys): array
    {
        $grouped = [];
        foreach ($surveys as $survey) {
            $grouped[(string) $survey->getOperator()->getId()][] = $survey;
        }
        return $grouped;
    }

    /** One operator's group, inside the caller's transaction: a row per survey, one digest email per user. @param non-empty-list<Survey> $surveys */
    private function sendDigest(array $surveys, NotificationType $type, \DateTimeImmutable $now): void
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

            // Year and month are maximised independently (as the former helper did); correct only because the
            // window restricts a group to one calendar month.
            $start = $survey->getSurveyPeriodStart();
            $mostRecentSurveyYear = max($mostRecentSurveyYear, (int) $start->format('Y'));
            $mostRecentSurveyMonth = max($mostRecentSurveyMonth, (int) $start->format('n'));

            $this->recorder->record($survey, $type, NotificationChannel::EMAIL, \DateTime::createFromImmutable($now));
        }

        $routeNames = array_values($routeNamesByRouteId);
        sort($routeNames);
        $routeNamesForEmail = join("\n", array_map(fn($name) => "* {$name}", $routeNames));
        $formattedMonth = (new \DateTime("{$mostRecentSurveyYear}-{$mostRecentSurveyMonth}-1 00:00:00"))->format('F');

        $users = [];
        foreach ($operator->getUsers() as $user) {
            $this->messageBus->dispatch(new Email(
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
            ));
            $users[] = $user->getEmail();
        }

        $this->logger->info(self::LOG_PREFIX."Sending reminders for '{$operator->getName()}': "
            .'route_names=['.implode('; ', array_map(fn($name) => "* {$name}", $routeNames))."], year={$mostRecentSurveyYear}, month={$formattedMonth}, users=[".implode(', ', $users).']');
    }
}
