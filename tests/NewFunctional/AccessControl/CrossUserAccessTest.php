<?php

namespace App\Tests\NewFunctional\AccessControl;

use App\Entity\International\Action;
use App\Entity\International\Trip;
use App\Entity\PasscodeUser;
use App\Entity\RoRo\Operator;
use App\Entity\RoRo\Survey as RoRoSurvey;
use App\Entity\RoRoUser;
use App\Tests\DataFixtures\Dast\RoRoReferenceFixtures;
use App\Tests\NewFunctional\AbstractFunctionalTestCase;
use App\Utility\Dev\DastSeeder;
use App\Utility\PasscodeGenerator;
use App\Workflow\InternationalSurvey\ActionState;
use App\Workflow\InternationalSurvey\TripState;
use App\Workflow\InternationalSurvey\VehicleState;
use App\Workflow\RoRo\RoRoState;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Cross-user access: a logged-in user requests every frontend route that takes a record ID (found from the router,
 * so new routes are swept too), with another user's IDs, by GET and POST. The answer must never be a 200, nor show
 * the other user's data. Uses the DAST seed data (App\Utility\Dev\DastSeeder): the "victims" are seed()'s
 * international and RoRo users, the "attackers" seedAccessControlUsers()'s.
 */
class CrossUserAccessTest extends AbstractFunctionalTestCase
{
    private const array ID_PARAMETERS = ['tripId', 'id', 'actionId', 'vehicleId', 'operatorId', 'surveyId'];

    // A valid {state} for each wizard route with an ID (an ID route with a {state} not listed here fails the sweep)
    private const array STATES = [
        'app_internationalsurvey_action_add_wizard' => ActionState::STATE_PLACE,
        'app_internationalsurvey_action_edit_wizard' => ActionState::STATE_PLACE,
        'app_internationalsurvey_trip_add_wizard' => TripState::STATE_ORIGIN_AND_DESTINATION,
        'app_internationalsurvey_trip_edit_wizard' => TripState::STATE_ORIGIN_AND_DESTINATION,
        'app_internationalsurvey_vehicle_edit_wizard' => VehicleState::STATE_REQUEST_VEHICLE_BODY,
        'app_roro_survey_edit' => RoRoState::STATE_COMMENTS,
    ];

    // Text that only the victims' pages show
    private const array VICTIM_MARKERS = ['Scanville', 'AA01ABC', 'AA01 ABC', 'Dast survey Calais', 'Dast survey Dunkirk', 'Dast survey Cuxhaven', 'Dast Ferries'];

    private EntityManagerInterface $entityManager;
    private DastSeeder $seeder;

    protected function setUp(): void
    {
        $this->initialiseClientAndLoadFixtures([RoRoReferenceFixtures::class], ['hostname' => 'rfs-frontend.localhost']);
        $this->client->followRedirects(false);
        static::getContainer()->get('cache.global_clearer')->clearPool('security.authenticator.cache.expired_links');
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->seeder = static::getContainer()->get(DastSeeder::class);
        $this->seeder->seed();
        $this->seeder->seedAccessControlUsers();
    }

    public function testInternationalUserCannotReachAnotherUsersRecords(): void
    {
        $this->passcodeLogin('90000004');
        // Control: the attacker can use its own pages (so a refusal below isn't just a broken login)
        $this->assertStatus(200, '/international-survey');
        $this->assertStatus(200, $this->path('app_internationalsurvey_trip_view', ['id' => $this->trip('Probeton')->getId()]));

        $violations = $this->sweep('app_internationalsurvey_', $this->victimInternationalIds());
        self::assertSame([], $violations, "International user 90000004 reached 90000002's records:\n" . implode("\n", $violations));
    }

    public function testRoRoUserCannotReachAnotherOperatorsRecords(): void
    {
        $this->roroLogin('dast-roro-outsider@example.com');
        // Control: the outsider can use its own dashboard
        $outsider = $this->entityManager->getRepository(Operator::class)->findOneBy(['name' => 'Outsider Ferries']);
        $this->assertStatus(200, $this->path('app_roro_dashboard', ['operatorId' => $outsider->getId()]));

        $violations = [];
        foreach ($this->victimRoRoIdSets() as $ids) {
            $violations = [...$violations, ...$this->sweep('app_roro_', $ids)];
        }
        self::assertSame([], $violations, "RoRo user outside the Dast Ferries group reached its records:\n" . implode("\n", $violations));
    }

    /** Control: the victims' own URLs work, so the refusals above aren't just bad URLs */
    public function testVictimsCanReachTheirOwnRecords(): void
    {
        $this->passcodeLogin(DastSeeder::USERNAMES['international']);
        $ids = $this->victimInternationalIds();
        foreach (['app_internationalsurvey_trip_view' => ['id' => $ids['id']], 'app_internationalsurvey_vehicle_view' => ['vehicleId' => $ids['vehicleId']], 'app_internationalsurvey_action_view' => ['actionId' => $ids['actionId']]] as $route => $parameters) {
            $this->assertStatus(200, $this->path($route, $parameters));
        }

        $this->roroLogin(DastSeeder::USERNAMES['roro']);
        foreach ($this->victimRoRoIdSets() as $ids) {
            $this->assertStatus(200, $this->path('app_roro_dashboard', ['operatorId' => $ids['operatorId']]));
            $this->assertStatus(200, $this->path('app_roro_survey_view', ['surveyId' => $ids['surveyId']]));
        }
    }

    /** Every frontend route with a record ID belongs to a swept area, and has a state if it needs one */
    public function testEveryIdRouteIsSwept(): void
    {
        foreach ($this->idRoutes() as $name => $route) {
            self::assertTrue(str_starts_with($name, 'app_internationalsurvey_') || str_starts_with($name, 'app_roro_'), "{$name} isn't swept");
            if (str_contains($route->getPath(), '{state}')) {
                self::assertArrayHasKey($name, self::STATES, "{$name} needs a state in CrossUserAccessTest::STATES");
            }
        }
    }

    /**
     * @param array<string, string> $ids parameter name => the victim's ID
     * @return list<string> violations
     */
    private function sweep(string $routePrefix, array $ids): array
    {
        $violations = [];
        $swept = 0;
        foreach ($this->idRoutes() as $name => $route) {
            if (!str_starts_with($name, $routePrefix)) {
                continue;
            }
            $parameters = [];
            foreach ($route->compile()->getPathVariables() as $variable) {
                if ($variable === 'state') {
                    $parameters['state'] = self::STATES[$name];
                } elseif (isset($ids[$variable])) {
                    $parameters[$variable] = $ids[$variable];
                } else {
                    continue 2;
                }
            }
            $path = $this->path($name, $parameters);
            foreach (['GET', 'POST'] as $method) {
                $this->client->request($method, $path);
                $response = $this->client->getResponse();
                $body = (string) $response->getContent();
                $shown = array_values(array_filter(self::VICTIM_MARKERS, fn(string $marker) => str_contains($body, $marker)));
                if ($response->getStatusCode() === 200 || $shown !== []) {
                    $violations[] = sprintf('%s %s (%s) -> %d%s', $method, $path, $name, $response->getStatusCode(), $shown ? ', shows ' . implode(', ', $shown) : '');
                }
                $swept++;
            }
        }
        self::assertGreaterThan(0, $swept, "No {$routePrefix} routes swept");
        return $violations;
    }

    /** @return array<string, \Symfony\Component\Routing\Route> */
    private function idRoutes(): array
    {
        $routes = [];
        foreach (static::getContainer()->get(RouterInterface::class)->getRouteCollection() as $name => $route) {
            if ($route->getHost() !== 'rfs-frontend.localhost') {
                continue;
            }
            if (array_intersect($route->compile()->getPathVariables(), self::ID_PARAMETERS) !== []) {
                $routes[$name] = $route;
            }
        }
        return $routes;
    }

    /** @return array<string, string> */
    private function victimInternationalIds(): array
    {
        $trip = $this->trip(DastSeeder::MARKERS['international-trip']);
        $action = $trip->getActions()->filter(fn(Action $action) => $action->getLoading())->first();
        self::assertInstanceOf(Action::class, $action);
        return ['tripId' => $trip->getId(), 'id' => $trip->getId(), 'vehicleId' => $trip->getVehicle()->getId(), 'actionId' => $action->getId()];
    }

    /** @return list<array<string, string>> one set per Dast Ferries operator */
    private function victimRoRoIdSets(): array
    {
        $sets = [];
        foreach (['Dast Ferries Dover' => 'Dast survey Calais', 'Dast Ferries Harwich' => 'Dast survey Cuxhaven'] as $operatorName => $comment) {
            $operator = $this->entityManager->getRepository(Operator::class)->findOneBy(['name' => $operatorName]);
            $survey = $this->entityManager->getRepository(RoRoSurvey::class)->findOneBy(['comments' => $comment]);
            $sets[] = ['operatorId' => $operator->getId(), 'surveyId' => $survey->getId()];
        }
        return $sets;
    }

    private function trip(string $origin): Trip
    {
        $trip = $this->entityManager->getRepository(Trip::class)->findOneBy(['origin' => $origin]);
        self::assertInstanceOf(Trip::class, $trip);
        return $trip;
    }

    private function path(string $route, array $parameters): string
    {
        return (string) parse_url(static::getContainer()->get(RouterInterface::class)->generate($route, $parameters), PHP_URL_PATH);
    }

    private function assertStatus(int $expected, string $path): void
    {
        $this->client->request('GET', $path);
        self::assertSame($expected, $this->client->getResponse()->getStatusCode(), "GET {$path}");
    }

    private function passcodeLogin(string $username): void
    {
        $user = $this->entityManager->getRepository(PasscodeUser::class)->findOneBy(['username' => $username]);
        $password = static::getContainer()->get(PasscodeGenerator::class)->getPasswordForUser($user);
        $this->client->getCookieJar()->clear();
        $this->client->followRedirects(true);
        $this->client->request('GET', '/login');
        $this->client->submitForm('passcode_login_sign_in', [
            'passcode_login[passcode][0]' => $username,
            'passcode_login[passcode][1]' => $password,
        ]);
        $this->client->followRedirects(false);
        self::assertStringNotContainsString('/login', $this->getCurrentUri(), "{$username} logged in");
    }

    private function roroLogin(string $email): void
    {
        $user = $this->entityManager->getRepository(RoRoUser::class)->findOneBy(['username' => $email]);
        $handler = static::getContainer()->get('security.authenticator.login_link_handler.roro');
        parse_str(DastSeeder::issueRoRoLoginLinks($handler, $user, 1)[0], $parameters);
        $this->client->getCookieJar()->clear();
        $this->client->request('POST', '/roro/authenticate', $parameters);
        self::assertMatchesRegularExpression('#/roro/operator/#', (string) $this->client->getResponse()->headers->get('Location'), "{$email} logged in");
    }
}
