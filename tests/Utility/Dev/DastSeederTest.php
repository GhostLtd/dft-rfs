<?php

namespace App\Tests\Utility\Dev;

use App\Entity\PasscodeUser;
use App\Tests\NewFunctional\AbstractFunctionalTestCase;
use App\Utility\Dev\DastSeeder;
use App\Utility\PasscodeGenerator;
use Doctrine\ORM\EntityManagerInterface;

class DastSeederTest extends AbstractFunctionalTestCase
{
    private const array EXPECTED_KEYS = [
        'DAST_DOMESTIC_USERNAME',
        'DAST_DOMESTIC_PASSWORD',
        'DAST_INTERNATIONAL_USERNAME',
        'DAST_INTERNATIONAL_PASSWORD',
        'DAST_PRE_ENQUIRY_USERNAME',
        'DAST_PRE_ENQUIRY_PASSWORD',
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
    ];

    private EntityManagerInterface $entityManager;
    private DastSeeder $seeder;

    protected function setUp(): void
    {
        // An empty database: the seeder refuses to run otherwise
        $this->initialiseClientAndLoadFixtures([], ['hostname' => 'rfs-frontend.localhost']);
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->seeder = static::getContainer()->get(DastSeeder::class);
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
