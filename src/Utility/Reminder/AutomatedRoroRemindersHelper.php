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
use Symfony\Contracts\Translation\TranslatorInterface;

class AutomatedRoroRemindersHelper
{
    public function __construct(
        protected DisabledRemindersHelper $disabledRemindersHelper,
        protected EntityManagerInterface $entityManager,
        protected LoggerInterface $logger,
        protected MessageBusInterface $messageBus,
        protected RouterInterface $router,
        protected SurveyRepository $surveyRepository,
        protected TranslatorInterface $translator,
    ) {}

    public function sendReminders(): void
    {
        $logPrefix = "Roro Reminders: ";

        if ($this->disabledRemindersHelper->isDisabled(DisabledRemindersHelper::RORO)) {
            $this->logger->info("{$logPrefix}Reminder sending disabled for RoRo. Exiting.");
            return;
        }

        $now = new \DateTime();
        foreach([1,2] as $reminderNumber) {
            $operators = $this->getRoroSurveysForReminderGroupedByOperatorAndRoute($reminderNumber);
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
                        if ($reminderNumber === 1) {
                            $survey->setFirstReminderSentDate($now);
                        } elseif ($reminderNumber === 2) {
                            $survey->setSecondReminderSentDate($now);
                        }
                    }
                }

                sort($routeNames);

                $routeNamesForEmail = join("\n", array_map(fn($name) => "* {$name}", $routeNames));

                foreach($operator->getUsers() as $user) {
                    $this->messageBus->dispatch(new Email(
                        $eventType,
                        get_class($user),
                        $user->getId(),
                        $user->getEmail(),
                        $template,
                        [
                            'operator' => $operator->getName(),
                            'route_names' => $routeNamesForEmail,
                            'login_link' => $this->router->generate('app_roro_login'),
                        ],
                    ));
                }

                $this->logger->info("{$logPrefix}Sending reminders for '{$operator->getName()}'");
            }
        }

        $this->entityManager->flush();
    }

    /**
     * @return array<string, array{operator: Operator, routes: array<string, array{route: Route, surveys: array<int, Survey>}>}>
     */
    protected function getRoroSurveysForReminderGroupedByOperatorAndRoute(int $reminderNumber): array
    {
        $queryBuilder = $this->surveyRepository
            ->createQueryBuilder('survey')
            ->select('survey, operator, user, route, ukPort, foreignPort')
            ->join('survey.operator', 'operator')
            ->join('operator.users', 'user')
            ->join('survey.route', 'route')
            ->join('route.ukPort', 'ukPort')
            ->join('route.foreignPort', 'foreignPort')
            ->where('survey.state IN (:nonCompletedStates)');

        if ($reminderNumber === 1) {
            $queryBuilder->andWhere('survey.firstReminderSentDate IS NULL');
        } elseif ($reminderNumber === 2) {
            $queryBuilder
                ->andWhere('survey.firstReminderSentDate IS NOT NULL')
                ->andWhere('survey.firstReminderSentDate <= :sevenDaysAgo')
                ->andWhere('survey.secondReminderSentDate IS NULL');

            $queryBuilder->setParameter('sevenDaysAgo', new \DateTime('7 days ago'));
        }

        $surveys = $queryBuilder
            ->setParameter('nonCompletedStates', [
                SurveyStateInterface::STATE_NEW,
                SurveyStateInterface::STATE_IN_PROGRESS,
            ])
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