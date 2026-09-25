<?php

namespace App\Tests\Utility\Reminder;

use App\DTO\RoRo\OperatorRoute;
use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\RoRo\Operator;
use App\Entity\RoRo\Survey;
use App\Entity\RoRo\SurveyNotification;
use App\Entity\Route\Route;
use App\Entity\RoRoUser;
use App\Tests\DataFixtures\Notification\ReminderScenarioFixtures;
use App\Tests\DataFixtures\RoRo\CountryFixtures;
use App\Tests\DataFixtures\RoRo\OperatorRouteFixtures;
use App\Tests\DataFixtures\RoRo\UserFixtures;
use App\Utility\AlphagovNotify\Reference;
use App\Utility\Notification\AutomatedNotificationsRunner;
use App\Utility\Notification\NotificationChannel;
use App\Utility\Notification\NotificationQueryHelper;
use App\Utility\Notification\NotificationRecorder;
use App\Utility\Notification\NotificationType;
use App\Utility\Reminder\DisabledRemindersHelper;
use App\Utility\Reminder\DomesticReminders;
use App\Utility\Reminder\InternationalReminders;
use App\Utility\Reminder\PreEnquiryReminders;
use App\Utility\Reminder\RoRoReminders;
use App\Utility\RoRo\SurveyCreationHelper;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Email;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Parity suite for RoRo's automated reminders, carried over from the former AutomatedRoroRemindersHelperTest:
 * RoRoReminders now owns RoRo's selection (last calendar month, active operator and route, route still
 * assigned), grouping by operator and digest emails outright. These
 * cases drive the real recorder, translator and login-link handler with only the message bus swapped for a
 * mock, so they prove the class sends exactly what the old helper sent: same surveys selected, same one
 * digest email per operator user, same personalisation, same cadence, same transaction boundary.
 *
 * "Now" is pinned to 2023-10-15 09:00 Europe/London throughout (except the disabled-family case, which
 * shares ReminderScenarioFixtures' own clock so it can exercise a CSRGT survey alongside a RoRo one) --
 * matching the dates baked into the old test's dataReminder()/dataSecondReminder().
 */
class RoRoRemindersTest extends KernelTestCase
{
    private const string NOW = '2023-10-15 09:00:00';

    private EntityManagerInterface $entityManager;
    private MessageBusInterface&MockObject $bus;
    private ReferenceRepository $refs;
    private SurveyCreationHelper $surveyCreationHelper;
    private MockClock $clock;
    /** @var list<object> */
    private array $dispatched = [];

    protected function setUp(): void
    {
        $container = static::getContainer();

        // Set before anything (including the fixtures below) can resolve the real message bus.
        $this->bus = $this->createMock(MessageBusInterface::class);
        $this->bus->method('dispatch')->willReturnCallback(function (object $m, array $s = []) {
            $this->dispatched[] = $m;
            return new Envelope($m, $s);
        });
        $container->set('app.test.message_bus', $this->bus);
        $this->dispatched = [];

        // RoRoReminders resolves ClockInterface via the 'clock' service, as does the
        // NotificationSender used elsewhere -- so both must share the same clock, or a dispatch
        // would record sentAt using the real wall-clock time instead of $now, and a mock date in
        // the future could then let reminder-2 fire in the very same run as reminder-1. Set before
        // the fixtures below too, so nothing resolves the real clock service first.
        $this->clock = new MockClock(self::NOW, 'Europe/London');
        $container->set('clock', $this->clock);

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->surveyCreationHelper = $container->get(SurveyCreationHelper::class);
        $this->refs = $container->get(DatabaseToolCollection::class)->get()->loadFixtures([
            CountryFixtures::class,
            OperatorRouteFixtures::class,
            UserFixtures::class,
        ])->getReferenceRepository();
    }

    /** RoRoReminders over the real recorder, translator and RoRo login-link handler, with the bus mocked and the clock pinned. */
    private function reminders(string $now = self::NOW): RoRoReminders
    {
        $container = static::getContainer();
        $this->clock->modify($now);

        return new RoRoReminders(
            $this->entityManager,
            new NotificationQueryHelper($this->entityManager),
            new NotificationRecorder($this->entityManager),
            $this->bus,
            $container->get(TranslatorInterface::class),
            $container->get('security.authenticator.login_link_handler.roro'),
            $this->clock,
            new NullLogger(),
        );
    }

    public static function dataReminder(): array
    {
        // N.B. Current year / month is pinned to October 2023 via MockClock in the test below.
        return [
            // Expected to send
            'Survey from previous month' => [
                [
                    'operator' => 'Test operator',
                    'route_names' => '* Portfoot to Waspwerp',
                    'year' => 2023,
                    'month' => 'September',
                ],
                [
                    [
                        'roro:route:1',
                        [
                            new \DateTime('2023-09-01 00:00:00'),
                        ],
                    ],
                ],
            ],
            // Expected to send, but only cares about last month
            'Survey with three months of backlog' => [
                [
                    'operator' => 'Test operator',
                    'route_names' => '* Portfoot to Waspwerp',
                    'year' => 2023,
                    'month' => 'September',
                ],
                [
                    [
                        'roro:route:1',
                        [
                            new \DateTime('2023-09-01 00:00:00'),
                            new \DateTime('2023-08-01 00:00:00'),
                            new \DateTime('2023-07-01 00:00:00'),
                        ],
                    ],
                ],
            ],
            // Won't send - only cares about last month
            // N.B. This particular scenario will never happen in normal usage
            'Survey but this month' => [
                null,
                [
                    [
                        'roro:route:1',
                        [
                            new \DateTime('2023-10-01 00:00:00'),
                        ],
                    ],
                ],
            ],
            // Won't send - only cares about last month
            'Survey but two months ago' => [
                null,
                [
                    [
                        'roro:route:1',
                        [
                            new \DateTime('2023-08-01 00:00:00'),
                        ],
                    ],
                ],
            ],
            // Won't send - route not assigned to operator (see fixtures)
            'Survey last month, but route not assigned to operator' => [
                null,
                [
                    [
                        'roro:route:2',
                        [
                            new \DateTime('2023-09-01 00:00:00'),
                        ],
                    ],
                ],
            ],
            // Won't send - inactive route (see fixtures)
            'Survey last month, but route inactive' => [
                null,
                [
                    [
                        'roro:route:3',
                        [
                            new \DateTime('2023-09-01 23:59:00'),
                        ],
                    ],
                ],
            ],
            // Will send - two surveys last month for separate routes
            'Surveys for distinct routes last month' => [
                [
                    'operator' => 'Test operator',
                    'route_names' => "* Portfoot to Waspwerp\n* Southester to Roysterdam",
                    'year' => 2023,
                    'month' => 'September',
                ],
                [
                    [
                        'roro:route:1',
                        [
                            new \DateTime('2023-09-01 23:59:00'),
                        ],
                    ],
                    [
                        'roro:route:4',
                        [
                            new \DateTime('2023-09-01 23:59:00'),
                        ],
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('dataReminder')]
    public function testReminder(?array $expectedPersonalisation, array $surveyRoutesAndDates): void
    {
        $operator = $this->refs->getReference('roro:operator:1', Operator::class);

        $createdSurveys = [];

        foreach ($surveyRoutesAndDates as [$routeReference, $surveyDateTimes]) {
            $route = $this->refs->getReference($routeReference, Route::class);
            $operatorRoute = new OperatorRoute($operator->getId(), $route->getId());

            foreach ($surveyDateTimes as $surveyDateTime) {
                $survey = $this->surveyCreationHelper->createSurvey($surveyDateTime, $operatorRoute);
                $this->entityManager->persist($survey);
                $createdSurveys[] = $survey;
            }
        }

        $this->entityManager->flush();

        $this->bus
            ->expects($expectedPersonalisation === null ? $this->never() : $this->once())
            ->method('dispatch')
            ->willReturnCallback(function (object $message) use ($expectedPersonalisation) {
                $this->assertInstanceOf(Email::class, $message);

                $actualPersonalisation = $message->getPersonalisation();
                foreach (['operator', 'route_names', 'year', 'month'] as $field) {
                    $this->assertEquals($expectedPersonalisation[$field], $actualPersonalisation[$field] ?? null);
                }

                return new Envelope(new \stdClass());
            });

        $this->reminders()->run();

        // A row is recorded only for surveys that actually fall within the reminder window (last
        // month); a case can create out-of-window surveys too (see 'Survey with three months of
        // backlog'), and those must not get a row even though the case overall is expected to send.
        $windowStart = new \DateTime('2023-09-01 00:00:00');
        $windowEnd = new \DateTime('2023-10-01 00:00:00');

        foreach ($createdSurveys as $survey) {
            $this->entityManager->refresh($survey);

            $expectRow = $expectedPersonalisation !== null
                && $survey->getSurveyPeriodStart() >= $windowStart
                && $survey->getSurveyPeriodStart() < $windowEnd;

            $rows = $survey->getNotificationsOfType(NotificationType::REMINDER_1, NotificationChannel::EMAIL);

            if ($expectRow) {
                self::assertCount(1, $rows, "expected a REMINDER_1/EMAIL notification row for survey {$survey->getId()}");
            } else {
                self::assertCount(0, $rows, "expected no REMINDER_1 notification row for survey {$survey->getId()}");
            }
        }
    }

    /**
     * Mirrors the inactive-route and unassigned-route cases in dataReminder() above, but for the
     * operator itself; kept as its own test rather than a dataReminder() row because it needs to
     * mutate the operator after fixtures load, which that provider's fixed (route, dates) shape
     * has no hook for.
     */
    public function testInactiveOperatorSendsNothing(): void
    {
        $operator = $this->refs->getReference('roro:operator:1', Operator::class);
        $operator->setIsActive(false);
        $this->entityManager->flush();

        $route = $this->refs->getReference('roro:route:1', Route::class);
        $operatorRoute = new OperatorRoute($operator->getId(), $route->getId());
        $survey = $this->surveyCreationHelper->createSurvey(new \DateTime('2023-09-01 00:00:00'), $operatorRoute);
        $this->entityManager->persist($survey);
        $this->entityManager->flush();

        $this->bus->expects($this->never())->method('dispatch');

        $this->reminders()->run();

        $this->entityManager->refresh($survey);
        self::assertCount(
            0,
            $survey->getNotificationsOfType(NotificationType::REMINDER_1, NotificationChannel::EMAIL),
            'no reminder-1 row recorded: the operator is inactive',
        );
    }

    public static function dataSecondReminder(): array
    {
        return [
            'First reminder sent 7 days ago -> second reminder sent' => [
                true,
                '-7 days',
                false,
            ],
            'First reminder sent only 2 days ago -> too soon' => [
                false,
                '-2 days',
                false,
            ],
            'Second reminder already sent -> not sent again' => [
                false,
                '-7 days',
                true,
            ],
        ];
    }

    #[DataProvider('dataSecondReminder')]
    public function testSecondReminder(bool $expectDispatch, string $firstReminderSentAtOffset, bool $secondReminderAlreadyExists): void
    {
        $operator = $this->refs->getReference('roro:operator:1', Operator::class);
        $route = $this->refs->getReference('roro:route:1', Route::class);
        $operatorRoute = new OperatorRoute($operator->getId(), $route->getId());

        $survey = $this->surveyCreationHelper->createSurvey(new \DateTime('2023-09-01 00:00:00'), $operatorRoute);
        $this->entityManager->persist($survey);
        $this->entityManager->flush();

        // The second-reminder cadence is computed from the (mocked) clock, not real wall-clock
        // time, so these offsets are relative to self::NOW, matching RoRoReminders' cutoff.
        $firstReminder = (new SurveyNotification())
            ->setType(NotificationType::REMINDER_1)
            ->setChannel(NotificationChannel::EMAIL)
            ->setSentAt(new \DateTime(self::NOW.' '.$firstReminderSentAtOffset));
        $survey->addNotification($firstReminder);
        $this->entityManager->persist($firstReminder);

        if ($secondReminderAlreadyExists) {
            $secondReminder = (new SurveyNotification())
                ->setType(NotificationType::REMINDER_2)
                ->setChannel(NotificationChannel::EMAIL)
                ->setSentAt(new \DateTime(self::NOW.' -1 day'));
            $survey->addNotification($secondReminder);
            $this->entityManager->persist($secondReminder);
        }

        $this->entityManager->flush();

        $this->bus
            ->expects($expectDispatch ? $this->once() : $this->never())
            ->method('dispatch')
            ->willReturnCallback(function (object $message) {
                $this->assertInstanceOf(Email::class, $message);
                $this->assertSame(Reference::EMAIL_RORO_REMINDER_2, $message->getTemplateId());

                return new Envelope(new \stdClass());
            });

        $this->reminders()->run();

        $this->entityManager->refresh($survey);
        $secondReminderRows = $survey->getNotificationsOfType(NotificationType::REMINDER_2, NotificationChannel::EMAIL);

        self::assertCount($secondReminderAlreadyExists ? 1 : ($expectDispatch ? 1 : 0), $secondReminderRows);
    }

    /**
     * APP_DISABLE_REMINDERS=roro must silence only RoRo; a CSRGT survey due at the same time still
     * sends. This needs a CSRGT survey too, so it shares ReminderScenarioFixtures' own clock
     * (2026-09-10) rather than the October 2023 clock the other cases above use, with an ad hoc
     * RoRo survey dated to fall in that clock's "last month" window (August 2026) instead.
     */
    public function testDisabledFamilySendsNothingForRoRoWhileOtherFamiliesAreUnaffected(): void
    {
        $container = static::getContainer();
        $refs = $container->get(DatabaseToolCollection::class)->get()->loadFixtures([
            CountryFixtures::class,
            OperatorRouteFixtures::class,
            UserFixtures::class,
            ReminderScenarioFixtures::class,
        ])->getReferenceRepository();

        $operator = $refs->getReference('roro:operator:1', Operator::class);
        $route = $refs->getReference('roro:route:1', Route::class);
        $roroSurvey = $this->surveyCreationHelper->createSurvey(
            new \DateTime('2026-08-15 00:00:00'),
            new OperatorRoute($operator->getId(), $route->getId()),
        );
        $this->entityManager->persist($roroSurvey);
        $this->entityManager->flush();

        $container = static::getContainer();
        $this->clock->modify(ReminderScenarioFixtures::NOW);
        (new AutomatedNotificationsRunner(
            [
                $container->get(DomesticReminders::class),
                $container->get(InternationalReminders::class),
                $container->get(PreEnquiryReminders::class),
                $container->get(RoRoReminders::class),
            ],
            new DisabledRemindersHelper('roro'),
            new NullLogger(),
        ))->run();

        $this->entityManager->refresh($roroSurvey);
        self::assertCount(
            0,
            $roroSurvey->getNotificationsOfType(NotificationType::REMINDER_1, NotificationChannel::EMAIL),
            'RoRo reminders are disabled: no row recorded even though the survey is due',
        );

        $this->entityManager->clear();
        $csrgtSurvey = $this->entityManager->find(DomesticSurvey::class, $refs->getReference('r1:due', DomesticSurvey::class)->getId());
        self::assertTrue($csrgtSurvey->hasNotification(NotificationType::REMINDER_1), 'CSRGT is unaffected by RoRo being disabled');
    }

    /**
     * RoRoReminders' candidate query joins operator.users, so an operator with more than one user
     * fans that join out to one result row per (survey, user) pair; this proves the fan-out is
     * absorbed correctly on both sides: one digest Email per operator user (not per row), and one
     * REMINDER_1/EMAIL notification row per survey (not one per user the survey happened to be
     * joined against).
     */
    public function testMultipleOperatorUsersEachGetOneEmailAndSurveysGetOneRowEach(): void
    {
        $operator = $this->refs->getReference('roro:operator:1', Operator::class);

        // UserFixtures gives the operator one user (test@example.com); add a second.
        $secondUser = (new RoRoUser())->setUsername('second@example.com');
        $operator->addUser($secondUser);
        $this->entityManager->persist($secondUser);
        $this->entityManager->flush();
        self::assertCount(2, $operator->getUsers());

        $route1 = $this->refs->getReference('roro:route:1', Route::class);
        $route4 = $this->refs->getReference('roro:route:4', Route::class);

        $surveyA = $this->surveyCreationHelper->createSurvey(new \DateTime('2023-09-01 00:00:00'), new OperatorRoute($operator->getId(), $route1->getId()));
        $surveyB = $this->surveyCreationHelper->createSurvey(new \DateTime('2023-09-15 00:00:00'), new OperatorRoute($operator->getId(), $route4->getId()));
        $this->entityManager->persist($surveyA);
        $this->entityManager->persist($surveyB);
        $this->entityManager->flush();

        $recipients = [];
        $personalisations = [];

        $this->bus
            ->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturnCallback(function (object $message) use (&$recipients, &$personalisations) {
                $this->assertInstanceOf(Email::class, $message);
                $recipients[] = $message->getEmailAddress();
                $personalisations[] = $message->getPersonalisation();

                return new Envelope(new \stdClass());
            });

        $this->reminders()->run();

        self::assertCount(2, array_unique($recipients), 'one Email per operator user, each to a distinct recipient');

        foreach (['route_names', 'year', 'month'] as $field) {
            self::assertSame(
                $personalisations[0][$field] ?? null,
                $personalisations[1][$field] ?? null,
                "both users' emails should carry identical '{$field}' personalisation",
            );
        }
        self::assertNotSame(
            $personalisations[0]['login_link'] ?? null,
            $personalisations[1]['login_link'] ?? null,
            "each user's email should carry its own login link",
        );

        foreach ([$surveyA, $surveyB] as $survey) {
            $this->entityManager->refresh($survey);
            self::assertCount(
                1,
                $survey->getNotificationsOfType(NotificationType::REMINDER_1, NotificationChannel::EMAIL),
                "expected exactly one REMINDER_1/EMAIL row for survey {$survey->getId()}, not one per user from the operator.users fetch-join",
            );
        }
    }

    public function testDigestDeduplicatesRouteNamesAndUsesNewestSurveyMonth(): void
    {
        $operator = $this->refs->getReference('roro:operator:1', Operator::class);
        $route1 = $this->refs->getReference('roro:route:1', Route::class);
        $route4 = $this->refs->getReference('roro:route:4', Route::class);

        // Same route twice, plus a second route: route_names must be de-duplicated per route, not per survey.
        foreach ([[$route1, '2023-09-01'], [$route1, '2023-09-15'], [$route4, '2023-09-20']] as [$route, $start]) {
            $this->entityManager->persist($this->surveyCreationHelper->createSurvey(new \DateTime($start), new OperatorRoute($operator->getId(), $route->getId())));
        }
        $this->entityManager->flush();

        $this->bus->expects($this->once())->method('dispatch')->willReturnCallback(function (object $message) {
            $this->assertInstanceOf(Email::class, $message);
            $this->assertSame('reminder-1', $message->getEventName());
            $this->assertSame(RoRoUser::class, $message->getOriginatingEntityClass());
            $this->assertSame(Reference::EMAIL_RORO_REMINDER_1, $message->getTemplateId());
            $p = $message->getPersonalisation();
            $this->assertSame('Test operator', $p['operator']);
            $this->assertSame("* Portfoot to Waspwerp\n* Southester to Roysterdam", $p['route_names']);
            $this->assertSame('2023', $p['year']);
            $this->assertSame('September', $p['month']);
            $this->assertStringStartsWith('http', $p['login_link']);
            return new Envelope(new \stdClass());
        });

        $this->reminders()->run();
    }

    public function testDueForReminder1SelectsLastCalendarMonthForActiveAssignedRoutes(): void
    {
        $operator = $this->refs->getReference('roro:operator:1', Operator::class);
        $route1 = $this->refs->getReference('roro:route:1', Route::class);
        $route2 = $this->refs->getReference('roro:route:2', Route::class); // active, not assigned to the operator
        $route3 = $this->refs->getReference('roro:route:3', Route::class); // assigned, inactive
        $route4 = $this->refs->getReference('roro:route:4', Route::class);

        $make = fn(Route $route, string $start) => $this->surveyCreationHelper->createSurvey(new \DateTime($start), new OperatorRoute($operator->getId(), $route->getId()));
        $septemberA = $make($route1, '2023-09-01 00:00:00');
        $septemberB = $make($route4, '2023-09-15 00:00:00');
        $august = $make($route1, '2023-08-01 00:00:00');
        $october = $make($route1, '2023-10-01 00:00:00');
        $unassigned = $make($route2, '2023-09-01 00:00:00');
        $inactive = $make($route3, '2023-09-01 00:00:00');
        foreach ([$septemberA, $septemberB, $august, $october, $unassigned, $inactive] as $s) {
            $this->entityManager->persist($s);
        }
        $this->entityManager->flush();

        $dueIds = array_map(fn(Survey $s) => $s->getId(), $this->reminders()->dueForReminder1($this->clock->now()));
        sort($dueIds);
        $expected = [$septemberA->getId(), $septemberB->getId()];
        sort($expected);
        self::assertSame($expected, $dueIds);
    }

    public function testDueForReminder2GatesOnTheLatestReminder1(): void
    {
        $operator = $this->refs->getReference('roro:operator:1', Operator::class);
        $route = $this->refs->getReference('roro:route:1', Route::class);
        $make = fn(string $start) => $this->surveyCreationHelper->createSurvey(new \DateTime($start), new OperatorRoute($operator->getId(), $route->getId()));

        $due = $make('2023-09-01 00:00:00');
        $tooSoon = $make('2023-09-02 00:00:00');
        $resent = $make('2023-09-03 00:00:00');
        foreach ([$due, $tooSoon, $resent] as $s) {
            $this->entityManager->persist($s);
        }
        $this->entityManager->flush();

        $reminder1 = fn(Survey $s, string $offset) => $s->addNotification((new SurveyNotification())
            ->setType(NotificationType::REMINDER_1)->setChannel(NotificationChannel::EMAIL)->setSentAt(new \DateTime(self::NOW.' '.$offset)));
        $reminder1($due, '-7 days');
        $reminder1($tooSoon, '-2 days');
        // An old row AND a recent row on the same survey: the cutoff applies to the LATEST reminder-1, so this
        // survey is not due -- under "any reminder-1 old enough" (the pre-fold-in helper) it would be.
        $reminder1($resent, '-10 days');
        $reminder1($resent, '-2 days');
        $this->entityManager->flush();

        $dueIds = array_map(fn(Survey $s) => $s->getId(), $this->reminders()->dueForReminder2($this->clock->now()));

        self::assertContains($due->getId(), $dueIds);
        self::assertNotContains($tooSoon->getId(), $dueIds);
        self::assertNotContains($resent->getId(), $dueIds, 'a survey whose latest reminder-1 is recent is not due, however old an earlier reminder-1 is');
    }

    /**
     * buildDigest() must be exactly the emails run() dispatches, and must itself record and dispatch nothing --
     * proving queueDigest() only adds the recording and dispatch around it, not different content.
     */
    public function testBuildDigestEqualsWhatRunDispatchesAndRecordsNothing(): void
    {
        $operator = $this->refs->getReference('roro:operator:1', Operator::class);
        $route1 = $this->refs->getReference('roro:route:1', Route::class);
        $route4 = $this->refs->getReference('roro:route:4', Route::class);

        foreach ([[$route1, '2023-09-01'], [$route1, '2023-09-15'], [$route4, '2023-09-20']] as [$route, $start]) {
            $this->entityManager->persist($this->surveyCreationHelper->createSurvey(new \DateTime($start), new OperatorRoute($operator->getId(), $route->getId())));
        }
        $this->entityManager->flush();

        $reminders = $this->reminders();
        $now = $this->clock->now();
        $due = $reminders->dueForReminder1($now);
        self::assertNotEmpty($due);

        $rowsBefore = (int) $this->entityManager->getConnection()->fetchOne('SELECT COUNT(*) FROM roro_survey_notification');
        $built = $reminders->buildDigest($due, NotificationType::REMINDER_1);
        self::assertSame($rowsBefore, (int) $this->entityManager->getConnection()->fetchOne('SELECT COUNT(*) FROM roro_survey_notification'), 'buildDigest records nothing');

        $reminders->run();
        $dispatchedR1 = array_values(array_filter($this->dispatched, fn(object $m) => $m instanceof Email && $m->getEventName() === 'reminder-1'));

        self::assertSame(
            array_map(fn(Email $m) => [$m->getEmailAddress(), $m->getTemplateId(), $m->getPersonalisation()['route_names']], $built),
            array_map(fn(Email $m) => [$m->getEmailAddress(), $m->getTemplateId(), $m->getPersonalisation()['route_names']], $dispatchedR1),
        );
    }

    public function testDueGroupsEachTypeByOperator(): void
    {
        $operator1 = $this->refs->getReference('roro:operator:1', Operator::class);
        $route1 = $this->refs->getReference('roro:route:1', Route::class);
        $route4 = $this->refs->getReference('roro:route:4', Route::class);

        // A second operator, with its own user and an active route assigned to it, so due() has two operators
        // to group: one item per operator, not one item overall.
        $operator2 = (new Operator())->setName('Second operator')->setCode(1012)->setIsActive(true);
        $operator2->addRoute($route4);
        $operator2User = (new RoRoUser())->setUsername('second-operator@example.com');
        $operator2->addUser($operator2User);
        $this->entityManager->persist($operator2);
        $this->entityManager->persist($operator2User);

        $this->entityManager->persist($this->surveyCreationHelper->createSurvey(new \DateTime('2023-09-01 00:00:00'), new OperatorRoute($operator1->getId(), $route1->getId())));
        $this->entityManager->persist($this->surveyCreationHelper->createSurvey(new \DateTime('2023-09-05 00:00:00'), new OperatorRoute($operator2->getId(), $route4->getId())));
        $this->entityManager->flush();

        $reminders = $this->reminders();
        $now = $this->clock->now();
        $due = $reminders->due($now);

        self::assertSame([NotificationType::REMINDER_1, NotificationType::REMINDER_2], $due->types);

        $r1Items = $due->ofType(NotificationType::REMINDER_1);
        self::assertNotEmpty($r1Items);
        self::assertCount(2, $r1Items, 'one item per operator');

        $r1Surveys = array_merge(...array_map(fn($i) => $i->surveys, $r1Items));
        self::assertEqualsCanonicalizing(
            array_map(fn($s) => $s->getId(), $reminders->dueForReminder1($now)),
            array_map(fn($s) => $s->getId(), $r1Surveys),
        );
        foreach ($due->items as $item) {
            self::assertCount(1, array_unique(array_map(fn($s) => $s->getOperator()->getId(), $item->surveys)), 'one operator per item');
        }
    }
}
