<?php

namespace App\Tests\Utility\Dev;

use App\Entity\PasscodeUser;
use App\Entity\RoRo\Operator;
use App\Entity\RoRo\OperatorGroup;
use App\Entity\RoRo\Survey as RoRoSurvey;
use App\Entity\RoRoUser;
use App\Entity\SurveyStateInterface;
use App\Tests\DataFixtures\Dast\RoRoReferenceFixtures;
use App\Tests\NewFunctional\AbstractFunctionalTestCase;
use App\Utility\Dev\DastSeeder;
use App\Utility\PasscodeGenerator;
use App\Utility\RoRo\OperatorSwitchHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\LoginLink\LoginLinkDetails;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;

class DastSeederTest extends AbstractFunctionalTestCase
{
    private const array EXPECTED_KEYS = [
        'DAST_DOMESTIC_USERNAME',
        'DAST_DOMESTIC_PASSWORD',
        'DAST_INTERNATIONAL_USERNAME',
        'DAST_INTERNATIONAL_PASSWORD',
        'DAST_PRE_ENQUIRY_USERNAME',
        'DAST_PRE_ENQUIRY_PASSWORD',
        'DAST_FORMS_DOMESTIC_USERNAME',
        'DAST_FORMS_DOMESTIC_PASSWORD',
        'DAST_FORMS_INTERNATIONAL_USERNAME',
        'DAST_FORMS_INTERNATIONAL_PASSWORD',
        'DAST_FORMS_PRE_ENQUIRY_USERNAME',
        'DAST_FORMS_PRE_ENQUIRY_PASSWORD',
        'DAST_PATH_DOMESTIC_STOPS',
        'DAST_PATH_DOMESTIC_SUMMARY',
        'DAST_PATH_INTERNATIONAL_TRIP',
        'DAST_PATH_PRE_ENQUIRY_SENTINEL',
        'DAST_PATH_ADMIN_SURVEY',
        'DAST_CHECK_DOMESTIC_STOPS',
        'DAST_CHECK_DOMESTIC_SUMMARY',
        'DAST_CHECK_INTERNATIONAL_TRIP',
        'DAST_CHECK_PRE_ENQUIRY',
        'DAST_CHECK_ADMIN_SURVEY',
        'DAST_RORO_LOGIN_LINKS',
        'DAST_PATH_RORO_HARWICH_DASHBOARD',
        'DAST_PATH_RORO_VEHICLE_COUNTS',
        'DAST_PATH_RORO_DATA_ENTRY',
        'DAST_PATH_RORO_COMMENTS',
        'DAST_CHECK_RORO_CALAIS',
        'DAST_CHECK_RORO_DUNKIRK',
        'DAST_CHECK_RORO_CUXHAVEN',
    ];

    private EntityManagerInterface $entityManager;
    private DastSeeder $seeder;

    protected function setUp(): void
    {
        // No users (the seeder refuses to run otherwise), but the RoRo reference data the migrations provide on the
        // DAST stack
        $this->initialiseClientAndLoadFixtures([RoRoReferenceFixtures::class], ['hostname' => 'rfs-frontend.localhost']);
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->seeder = static::getContainer()->get(DastSeeder::class);

        // RoRo login links are deterministic (user + expiry, to the second), and their used-once record lives in a
        // cache pool that outlives a test: without this, a later test's links can already be spent
        static::getContainer()->get('cache.global_clearer')->clearPool('security.authenticator.cache.expired_links');
    }

    public function testSeedCreatesOneUserPerSurveyType(): void
    {
        $output = $this->seeder->seed();
        $passcodeGenerator = static::getContainer()->get(PasscodeGenerator::class);

        $prefixes = ['domestic' => 'DAST_DOMESTIC', 'international' => 'DAST_INTERNATIONAL', 'pre-enquiry' => 'DAST_PRE_ENQUIRY'];
        foreach ($prefixes as $type => $prefix) {
            self::assertSame(DastSeeder::USERNAMES[$type], $output["{$prefix}_USERNAME"]);
            self::assertMatchesRegularExpression('/^\d{8}$/', $output["{$prefix}_PASSWORD"]);
            self::assertSame($passcodeGenerator->getPasswordForUser($this->getUser($type)), $output["{$prefix}_PASSWORD"]);
        }
    }

    public function testDomesticSurveyHasDaysStopsAndSummary(): void
    {
        $this->seeder->seed();
        $user = $this->getUser('domestic');

        self::assertNull($user->getInternationalSurvey());
        self::assertNull($user->getPreEnquiry());

        $response = $user->getDomesticSurvey()?->getResponse();
        self::assertNotNull($response);
        self::assertNotNull($response->getVehicle());

        $stopCounts = [];
        $summaryCount = 0;
        foreach ($response->getDays() as $day) {
            $stopCounts[] = $day->getStops()->count();
            $summaryCount += $day->getSummary() ? 1 : 0;
        }
        self::assertGreaterThanOrEqual(2, max($stopCounts));
        self::assertGreaterThanOrEqual(1, $summaryCount);
    }

    public function testInternationalSurveyHasTripWithActions(): void
    {
        $this->seeder->seed();
        $user = $this->getUser('international');

        self::assertNull($user->getDomesticSurvey());
        self::assertNull($user->getPreEnquiry());

        $vehicles = $user->getInternationalSurvey()?->getResponse()?->getVehicles();
        self::assertNotNull($vehicles);
        self::assertCount(1, $vehicles);
        $trips = $vehicles->first()->getTrips();
        self::assertCount(1, $trips);
        self::assertGreaterThanOrEqual(2, $trips->first()->getActions()->count());
    }

    public function testPreEnquiryHasResponse(): void
    {
        $this->seeder->seed();
        $user = $this->getUser('pre-enquiry');

        self::assertNull($user->getDomesticSurvey());
        self::assertNull($user->getInternationalSurvey());
        self::assertNotNull($user->getPreEnquiry()?->getResponse());
    }

    public function testOutputHasEveryKey(): void
    {
        $output = $this->seeder->seed();

        self::assertSame(self::EXPECTED_KEYS, array_keys($output));
        foreach ($output as $key => $value) {
            self::assertNotSame('', $value, $key);
            if (str_starts_with($key, 'DAST_PATH_') || str_starts_with($key, 'DAST_CHECK_')) {
                self::assertMatchesRegularExpression('#^/[^/]#', $value, $key);
            }
        }
    }

    public function testMarkersArePlainText(): void
    {
        foreach (DastSeeder::MARKERS as $marker) {
            self::assertMatchesRegularExpression('/^[A-Za-z0-9 ]+$/', $marker);
        }
    }

    public function testMarkersMatchBinDast(): void
    {
        // bin/dast renders the markers into the ZAP plans as literal regexes
        $script = file_get_contents(__DIR__ . '/../../../bin/dast');
        foreach (DastSeeder::MARKERS as $key => $marker) {
            self::assertStringContainsString("[{$key}]='{$marker}'", $script, "bin/dast's marker for {$key}");
        }
    }

    public function testRoRoUserOperatorsAndGroup(): void
    {
        $this->seeder->seed();
        $this->entityManager->clear();

        $group = $this->entityManager->getRepository(OperatorGroup::class)->findOneBy(['name' => 'Dast Ferries']);
        self::assertInstanceOf(OperatorGroup::class, $group);

        $dover = $this->entityManager->getRepository(Operator::class)->findOneBy(['name' => 'Dast Ferries Dover']);
        $harwich = $this->entityManager->getRepository(Operator::class)->findOneBy(['name' => 'Dast Ferries Harwich']);
        self::assertInstanceOf(Operator::class, $dover);
        self::assertInstanceOf(Operator::class, $harwich);
        self::assertTrue($dover->isIsActive());
        self::assertTrue($harwich->isIsActive());

        $user = $this->getRoRoUser();
        self::assertSame($dover->getId(), $user->getOperator()->getId());

        $targets = static::getContainer()->get(OperatorSwitchHelper::class)->getOperatorSwitchTargets($dover);
        self::assertContains($harwich->getId(), array_map(fn(Operator $operator) => $operator->getId(), $targets));
    }

    public function testRoRoSurveysAreActiveAndInProgress(): void
    {
        $this->seeder->seed();
        $this->entityManager->clear();

        $surveys = $this->entityManager->getRepository(RoRoSurvey::class)->findAll();
        self::assertCount(3, $surveys);

        $comments = [];
        foreach ($surveys as $survey) {
            self::assertSame(SurveyStateInterface::STATE_IN_PROGRESS, $survey->getState());
            self::assertTrue($survey->getIsActiveForPeriod());
            self::assertNotNull($survey->getDataEntryMethod());
            $counted = array_filter($survey->getVehicleCounts()->toArray(), fn($count) => ($count->getVehicleCount() ?? 0) > 0);
            self::assertNotEmpty($counted);
            $comments[] = $survey->getComments();
        }

        self::assertEqualsCanonicalizing(
            [DastSeeder::MARKERS['roro-calais'], DastSeeder::MARKERS['roro-dunkirk'], DastSeeder::MARKERS['roro-cuxhaven']],
            $comments
        );
    }

    public function testRoRoLoginLinksAreDistinctAndEachWorksOnce(): void
    {
        $bodies = explode(' ', $this->seeder->seed()['DAST_RORO_LOGIN_LINKS']);

        self::assertCount(DastSeeder::RORO_LOGIN_LINKS, $bodies);
        self::assertCount(DastSeeder::RORO_LOGIN_LINKS, array_unique($bodies));

        foreach ($bodies as $i => $body) {
            $this->postLoginLink($body);
            self::assertMatchesRegularExpression('#/roro/operator/[^/]+$#', $this->getCurrentUri(), "Link {$i} logs in");
        }

        $this->postLoginLink($bodies[0]);
        self::assertStringEndsWith('/roro/login', $this->getCurrentUri(), 'A used link is refused');
    }

    public function testRoRoLoginLinksStayDistinctAcrossClockTicks(): void
    {
        $user = (new RoRoUser())->setUsername('tick@example.com');

        // Symfony's handler uses expires = time() + lifetime. This one does the same with a clock that ticks before
        // every call (decreasing lifetimes would give one distinct link)
        $handler = new class implements LoginLinkHandlerInterface {
            private int $now = 1_800_000_000;

            public function createLoginLink(UserInterface $user, ?Request $request = null, ?int $lifetime = null): LoginLinkDetails
            {
                $this->now++;
                $expires = $this->now + $lifetime;
                return new LoginLinkDetails("https://example.com/roro/authenticate?expires={$expires}&hash=h{$expires}", new \DateTimeImmutable("@{$expires}"));
            }

            public function consumeLoginLink(Request $request): UserInterface
            {
                throw new \LogicException('Not used');
            }
        };

        $bodies = DastSeeder::issueRoRoLoginLinks($handler, $user, 20);
        self::assertCount(20, array_unique($bodies));
    }

    public function testRoRoLinkPoolSizeIsValidated(): void
    {
        foreach ([0, 101] as $size) {
            try {
                $this->seeder->seed($size);
                self::fail("A pool of {$size} is refused");
            } catch (\InvalidArgumentException) {
                self::assertSame(0, $this->entityManager->getRepository(PasscodeUser::class)->count([]), 'Nothing written');
            }
        }
    }

    public function testRoRoPagesShowMarkers(): void
    {
        $output = $this->seeder->seed();
        $this->postLoginLink(explode(' ', $output['DAST_RORO_LOGIN_LINKS'])[0]);

        foreach (['DAST_PATH_RORO_HARWICH_DASHBOARD', 'DAST_PATH_RORO_VEHICLE_COUNTS', 'DAST_PATH_RORO_DATA_ENTRY', 'DAST_PATH_RORO_COMMENTS'] as $key) {
            $this->assertPageShows($output[$key]);
        }

        $checks = ['DAST_CHECK_RORO_CALAIS' => 'roro-calais', 'DAST_CHECK_RORO_DUNKIRK' => 'roro-dunkirk', 'DAST_CHECK_RORO_CUXHAVEN' => 'roro-cuxhaven'];
        $paths = array_filter($output, fn(string $key) => str_starts_with($key, 'DAST_PATH_RORO_') || str_starts_with($key, 'DAST_CHECK_RORO_'), ARRAY_FILTER_USE_KEY);
        foreach ($checks as $key => $marker) {
            $this->assertPageShows($output[$key], DastSeeder::MARKERS[$marker]);
            foreach ($paths as $path) {
                self::assertStringStartsNotWith($output[$key] . '/', $path, "{$key} is a leaf");
            }
        }

        $this->assertPageShows('/roro/switch-operator', 'href="/roro/logout"');
    }

    public function testRefusesDatabaseWithOnlyRoRoUsers(): void
    {
        $operator = (new Operator())->setName('Someone Else')->setCode(901)->setIsActive(true);
        $this->entityManager->persist($operator);
        $this->entityManager->persist((new RoRoUser())->setUsername('someone@example.com')->setOperator($operator));
        $this->entityManager->flush();

        try {
            $this->seeder->seed();
            self::fail('Refused');
        } catch (\RuntimeException) {
        }

        $this->entityManager->clear();
        self::assertSame(0, $this->entityManager->getRepository(PasscodeUser::class)->count([]));
        self::assertSame(1, $this->entityManager->getRepository(Operator::class)->count([]));
        self::assertSame(0, $this->entityManager->getRepository(RoRoSurvey::class)->count([]));
    }

    public function testRefusesNonEmptyDatabase(): void
    {
        $this->seeder->seed();

        $this->expectException(\RuntimeException::class);
        $this->seeder->seed();
    }

    /**
     * The sentinels (DAST_PATH_*) are deep pages the spider must find; the live checks (DAST_CHECK_*) must show the
     * seeded markers. The checks are leaf pages: ZAP's URL test resolves a URL that is also a folder in its sites tree
     * (e.g. day-1, once day-1/stop-1/... is known) to the folder, not to the page's latest response.
     */
    public function testPagesShowMarkers(): void
    {
        $output = $this->seeder->seed();

        $this->login($output['DAST_DOMESTIC_USERNAME'], $output['DAST_DOMESTIC_PASSWORD']);
        $this->assertPageShows($output['DAST_PATH_DOMESTIC_STOPS']);
        $this->assertPageShows($output['DAST_PATH_DOMESTIC_SUMMARY']);
        $this->assertPageShows($output['DAST_CHECK_DOMESTIC_STOPS'], DastSeeder::MARKERS['domestic-stops']);
        $this->assertPageShows($output['DAST_CHECK_DOMESTIC_SUMMARY'], DastSeeder::MARKERS['domestic-summary']);

        $this->login($output['DAST_INTERNATIONAL_USERNAME'], $output['DAST_INTERNATIONAL_PASSWORD']);
        $this->assertPageShows($output['DAST_PATH_INTERNATIONAL_TRIP']);
        $this->assertPageShows($output['DAST_CHECK_INTERNATIONAL_TRIP'], DastSeeder::MARKERS['international-trip']);

        $this->login($output['DAST_PRE_ENQUIRY_USERNAME'], $output['DAST_PRE_ENQUIRY_PASSWORD']);
        $this->assertPageShows('/pre-enquiry');
        self::assertGreaterThan(
            0,
            $this->client->getCrawler()->filter(sprintf('a[href="%s"]', $output['DAST_PATH_PRE_ENQUIRY_SENTINEL']))->count(),
            'The pre-enquiry summary links to the sentinel page'
        );
        $this->assertPageShows($output['DAST_PATH_PRE_ENQUIRY_SENTINEL']);
        $this->assertPageShows($output['DAST_CHECK_PRE_ENQUIRY'], DastSeeder::MARKERS['pre-enquiry']);

        $this->client->request('GET', '/logout');
        $this->client->setServerParameter('HTTP_HOST', 'rfs-admin.localhost');
        $this->client->setServerParameter('TEST_GOOGLE_IAP_AUTH_USER', 'dast-admin@example.com');
        $this->assertPageShows($output['DAST_PATH_ADMIN_SURVEY']);
        $this->assertPageShows($output['DAST_CHECK_ADMIN_SURVEY'], DastSeeder::MARKERS['admin-survey']);
    }

    private function getUser(string $type): PasscodeUser
    {
        $this->entityManager->clear();
        $user = $this->entityManager->getRepository(PasscodeUser::class)->findOneBy(['username' => DastSeeder::USERNAMES[$type]]);
        self::assertInstanceOf(PasscodeUser::class, $user);
        return $user;
    }

    private function getRoRoUser(): RoRoUser
    {
        $user = $this->entityManager->getRepository(RoRoUser::class)->findOneBy(['username' => DastSeeder::USERNAMES['roro']]);
        self::assertInstanceOf(RoRoUser::class, $user);
        return $user;
    }

    /** POSTs a login link's body to /roro/authenticate, as the link's page does, from a fresh cookie jar */
    private function postLoginLink(string $body): void
    {
        parse_str($body, $parameters);
        $this->client->getCookieJar()->clear();
        $this->client->request('POST', '/roro/authenticate', $parameters);
    }

    private function login(string $username, string $password): void
    {
        $this->client->request('GET', '/logout');
        $this->client->request('GET', '/login');
        $this->client->submitForm('passcode_login_sign_in', [
            'passcode_login[passcode][0]' => $username,
            'passcode_login[passcode][1]' => $password,
        ]);
        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString('/login', $this->getCurrentUri(), 'Logged in');
    }

    private function assertPageShows(string $path, ?string $marker = null): void
    {
        $this->client->request('GET', $path);
        self::assertResponseIsSuccessful("GET {$path}");
        self::assertStringContainsString($path, $this->getCurrentUri(), "No redirect from {$path}");
        if ($marker !== null) {
            self::assertStringContainsString($marker, $this->client->getResponse()->getContent(), "{$path} shows '{$marker}'");
        }
    }
}
