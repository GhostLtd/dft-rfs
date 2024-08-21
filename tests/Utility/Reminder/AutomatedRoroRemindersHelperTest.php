<?php

namespace App\Tests\Utility\Reminder;

use App\DTO\RoRo\OperatorRoute;
use App\Entity\RoRo\Operator;
use App\Entity\Route\Route;
use App\Messenger\AlphagovNotify\Email;
use App\Tests\DataFixtures\RoRo\CountryFixtures;
use App\Tests\DataFixtures\RoRo\OperatorRouteFixtures;
use App\Tests\DataFixtures\RoRo\UserFixtures;
use App\Utility\Reminder\AutomatedRoroRemindersHelper;
use App\Utility\RoRo\SurveyCreationHelper;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class AutomatedRoroRemindersHelperTest extends KernelTestCase
{
    protected AutomatedRoroRemindersHelper $automatedRoroRemindersHelper;
    protected EntityManagerInterface $entityManager;
    protected MessageBusInterface|MockObject $messageBusMock;
    protected ReferenceRepository $referenceRepository;
    protected SurveyCreationHelper $surveyCreationHelper;

    #[\Override]
    public function setUp(): void
    {
        $container = static::getContainer();

        $this->messageBusMock = $this->getMockBuilder(MessageBusInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['dispatch'])
            ->getMock();

        $container->set('app.test.message_bus', $this->messageBusMock);

        $this->automatedRoroRemindersHelper = $container->get(AutomatedRoroRemindersHelper::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $databaseTool = $container->get(DatabaseToolCollection::class)->get();
        $this->surveyCreationHelper = $container->get(SurveyCreationHelper::class);

        $fixtures = $databaseTool->loadFixtures([
            CountryFixtures::class,
            OperatorRouteFixtures::class,
            UserFixtures::class,
        ]);

        $this->referenceRepository = $fixtures->getReferenceRepository();
    }

    public function dataReminder(): array
    {
        // N.B. Current year / month is hard-coded to October 2023 and passed into sendReminders() below, in the test
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
                        ]
                    ]
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
                        ]
                    ]
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
                        ]
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
                        ]
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
                        ]
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
                        ]
                    ]
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
                        ]
                    ]
                ],
            ],
        ];
    }

    /**
     * @dataProvider dataReminder
     */
    public function testReminder(?array $expectedPersonalisation, array $surveyRoutesAndDates): void
    {
        // Add surveys for given routes and dates
        $operator = $this->referenceRepository->getReference('roro:operator:1', Operator::class);
        $this->assertInstanceOf(Operator::class, $operator);

        foreach ($surveyRoutesAndDates as $surveyRouteAndDates) {
            $routeReference = $surveyRouteAndDates[0];
            $surveyDateTimes = $surveyRouteAndDates[1];

            $route = $this->referenceRepository->getReference($routeReference, Route::class);
            $this->assertInstanceOf(Route::class, $route);

            $operatorRoute = new OperatorRoute($operator->getId(), $route->getId());

            foreach ($surveyDateTimes as $surveyDateTime) {
                $survey = $this->surveyCreationHelper->createSurvey($surveyDateTime, $operatorRoute);
                $this->entityManager->persist($survey);
            }
        }

        $this->entityManager->flush();

        // Set up expectations for messageBus->dispatch to be called (or not), when sendReminders() is called
        $this->messageBusMock
            ->expects(
                $expectedPersonalisation === null ?
                    $this->never() :
                    $this->once()
            )
            ->method('dispatch')
            ->willReturnCallback(function (object $message) use ($expectedPersonalisation) {
                $this->assertInstanceOf(Email::class, $message);

                $actualPersonalisation = $message->getPersonalisation();
                foreach (['operator', 'route_names', 'year', 'month'] as $field) {
                    $this->assertEquals($expectedPersonalisation[$field], $actualPersonalisation[$field] ?? null);
                }

                return new Envelope(new \stdClass());
            });

        $this->automatedRoroRemindersHelper->sendReminders(
            '2023-09-01 00:00:00',
            '2023-10-01 00:00:00',
        );
    }
}
