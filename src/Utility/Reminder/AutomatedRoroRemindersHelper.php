<?php

namespace App\Utility\Reminder;

use App\Entity\RoRo\Operator;
use App\Entity\RoRo\Survey;
use App\Entity\Route\Route;
use App\Entity\SurveyStateInterface;
use App\Messenger\AlphagovNotify\Email;
use App\Repository\RoRo\SurveyRepository;
use App\Utility\AlphagovNotify\Reference;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AutomatedRoroRemindersHelper
{
    public function __construct(
        protected DisabledRemindersHelper $disabledRemindersHelper,
        protected EntityManagerInterface $entityManager,
        protected LoggerInterface $logger,
        protected MessageBusInterface $messageBus,
        protected LoginLinkHandlerInterface $roroLoginLinkHandler,
        protected RouterInterface $router,
        protected SurveyRepository $surveyRepository,
        protected TranslatorInterface $translator,
    ) {}

    public function sendReminders(
        // These args are used for testing, so that fixtures can use static dates...
        string $lastMonthDateString = 'first day of last month midnight',
        string $thisMonthDateString = 'first day of this month midnight',
    ): void
    {
        $logPrefix = "Roro Reminders: ";

        if ($this->disabledRemindersHelper->isDisabled(DisabledRemindersHelper::RORO)) {
            $this->logger->info("{$logPrefix}Reminder sending disabled for RoRo. Exiting.");
            return;
        }

        $now = new \DateTime();
        foreach([1,2] as $reminderNumber) {
            $operators = $this->getRoroSurveysForReminderGroupedByOperatorAndRoute($reminderNumber, $lastMonthDateString, $thisMonthDateString);
            $template = [
                1 => Reference::EMAIL_RORO_REMINDER_1,
                2 => Reference::EMAIL_RORO_REMINDER_2,
            ][$reminderNumber];
            $eventType = [
                1 => Reference::EVENT_REMINDER_1,
                2 => Reference::EVENT_REMINDER_2
            ][$reminderNumber];

            foreach ($operators as $groupedOperator) {
                /** @var Operator $operator */
                $operator = $groupedOperator['operator'];
                $routes = $groupedOperator['routes'];
                $routeNames = [];

                $mostRecentSurveyMonth = null;
                $mostRecentSurveyYear = null;

                // N.B. PHPStorm doesn't seem to fully support nested arrays, so I need to repeat myself here
                // https://youtrack.jetbrains.com/issue/WI-67555
                /** @var array<string, array{route: Route, surveys: array<int, Survey>}> $routes */
                foreach($routes as $groupedRoute) {
                    $route = $groupedRoute['route'];
                    $surveys = $groupedRoute['surveys'];

                    $routeNames[] = $this->translator->trans('roro.route', [
                        'ukPort' => $route->getUkPort()->getName(),
                        'foreignPort' => $route->getForeignPort()->getName(),
                    ]);

                    foreach($surveys as $survey) {
                        $surveyPeriodStart = $survey->getSurveyPeriodStart();

                        $mostRecentSurveyYear = max($mostRecentSurveyYear, intval($surveyPeriodStart->format('Y')));
                        $mostRecentSurveyMonth = max($mostRecentSurveyMonth, intval($surveyPeriodStart->format('n')));

                        if ($reminderNumber === 1) {
                            $survey->setFirstReminderSentDate($now);
                        } elseif ($reminderNumber === 2) {
                            $survey->setSecondReminderSentDate($now);
                        }
                    }
                }

                sort($routeNames);

                $routeNamesForEmail = join("\n", array_map(fn($name) => "* {$name}", $routeNames));
                $formattedMostRecentSurveyMonth = (new \DateTime("{$mostRecentSurveyYear}-{$mostRecentSurveyMonth}-1 00:00:00"))->format('F');

                $messageSentForUsers = [];
                foreach($operator->getUsers() as $user) {
                    $this->messageBus->dispatch(new Email(
                        $eventType,
                        $user::class,
                        $user->getId(),
                        $user->getEmail(),
                        $template,
                        [
                            'operator' => $operator->getName(),
                            'route_names' => $routeNamesForEmail,
                            'login_link' => $this->roroLoginLinkHandler->createLoginLink($user)->getUrl(),
                            'year' => $mostRecentSurveyYear,
                            'month' => $formattedMostRecentSurveyMonth,
                        ],
                    ));

                    $messageSentForUsers[] = $user->getEmail();
                }

                $this->logger->info("{$logPrefix}Sending reminders for '{$operator->getName()}'", [
                    'route_names' => $routeNames,
                    'year' => $mostRecentSurveyYear,
                    'month' => $formattedMostRecentSurveyMonth,
                    'users' => $messageSentForUsers,
                ]);
            }
        }

        $this->entityManager->flush();
    }

    /**
     * @return array<string, array{operator: Operator, routes: array<string, array{route: Route, surveys: array<int, Survey>}>}>
     */
    protected function getRoroSurveysForReminderGroupedByOperatorAndRoute(
        int $reminderNumber,
        string $lastMonthDateString,
        string $thisMonthDateString,
    ): array
    {
        $queryBuilder = $this->surveyRepository
            ->createQueryBuilder('survey')
            ->select('survey, operator, user, route, ukPort, foreignPort')
            ->join('survey.operator', 'operator')
            ->join('operator.users', 'user')
            ->join('survey.route', 'route')
            ->join('route.ukPort', 'ukPort')
            ->join('route.foreignPort', 'foreignPort')
            ->where('survey.state IN (:nonCompletedStates)')
            ->andWhere('survey.surveyPeriodStart >= (:monthMoreThanOrEqualTo)')
            ->andWhere('survey.surveyPeriodStart < (:monthLessThan)');

        if ($reminderNumber === 1) {
            $queryBuilder->andWhere('survey.firstReminderSentDate IS NULL');
        } elseif ($reminderNumber === 2) {
            $queryBuilder
                ->andWhere('survey.firstReminderSentDate IS NOT NULL')
                ->andWhere('survey.firstReminderSentDate <= :sevenDaysAgo')
                ->andWhere('survey.secondReminderSentDate IS NULL');

            // If we use exactly 7 days, we're likely to end up not sending the second remind until day 9 rather than
            // day 8, so a lesser amount is used here to make sure we actually send on day 8.
            $queryBuilder->setParameter('sevenDaysAgo', new \DateTime('6 days 12 hours ago'));
        }

        $surveyStartDate = new \DateTime($lastMonthDateString);
        $surveyEndedDate = new \DateTime($thisMonthDateString);

        $surveys = $queryBuilder
            ->setParameter('nonCompletedStates', [
                SurveyStateInterface::STATE_NEW,
                SurveyStateInterface::STATE_IN_PROGRESS,
            ])
            // N.B. If you pass times in addition to dates, this doesn't work (at least for sqlite)
            ->setParameter('monthMoreThanOrEqualTo', $surveyStartDate->format('Y-m-d'))
            ->setParameter('monthLessThan', $surveyEndedDate->format('Y-m-d'))
            ->getQuery()
            ->execute();

        $grouped = [];

        /** @var Survey $survey */
        foreach($surveys as $survey) {
            $operator = $survey->getOperator();
            $route = $survey->getRoute();

            if (!$operator->isIsActive() ||
                !$route->getIsActive() ||
                !$operator->hasRoute($route) // A route can be unassigned from an Operator, but the surveys still exist.
            ) {
                continue;
            }

            $operatorId = $operator->getId();
            $grouped[$operatorId] ??= [
                'operator' => $operator,
                'routes' => [],
            ];

            $routeId = $route->getId();
            $grouped[$operatorId]['routes'][$routeId] ??= [
                'route' => $route,
                'surveys' => [],
            ];
            $grouped[$operatorId]['routes'][$routeId]['surveys'][] = $survey;
        }

        return $grouped;
    }
}