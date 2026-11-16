<?php

namespace App\Tests\MySQL;

use App\Tests\DataFixtures\MySQL\ViewCoverageFixtures;
use App\Utility\Domestic\WeekNumberHelper as DomesticWeekNumberHelper;
use Doctrine\DBAL\Connection;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The views in sql/views/ (exported to DfT) against data on MySQL 8.0.18, as production builds them.
 *
 * Every view must return rows from ViewCoverageFixtures (so a new view without fixture coverage fails here), and the
 * columns the views derive in SQL, rather than copy from a table, are checked against known values or the PHP they
 * must agree with.
 */
class ViewsTest extends KernelTestCase
{
    use RunsOnMySql;

    private static bool $fixturesLoaded = false;

    private Connection $connection;
    private AbstractDatabaseTool $databaseTool;

    #[\Override]
    protected function setUp(): void
    {
        static::bootKernel();
        $container = static::getContainer();
        $this->connection = $container->get('doctrine.dbal.default_connection');
        $this->databaseTool = $container->get(DatabaseToolCollection::class)->get();
    }

    public static function dataViews(): array
    {
        $views = [];
        foreach (glob(dirname(__DIR__, 2) . '/sql/views/*.sql') as $file) {
            $name = basename($file, '.sql');
            $views[$name] = [$name];
        }
        return $views;
    }

    #[DataProvider('dataViews')]
    public function testViewReturnsRows(string $view): void
    {
        $this->loadCoverageFixtures();

        $this->assertGreaterThan(
            0,
            (int) $this->connection->fetchOne("SELECT COUNT(*) FROM `{$view}`"),
            "{$view} returned no rows: extend tests/DataFixtures/MySQL/ViewCoverageFixtures so it has qualifying data",
        );
    }

    public function testDomesticDriverAvailability(): void
    {
        $this->loadCoverageFixtures();

        $row = $this->fetchOnlyRow('SELECT * FROM view_export_domestic_driver_availability');
        [$expectedWeek, $expectedYear] = DomesticWeekNumberHelper::getYearlyWeekNumberAndYear(new \DateTime('2021-05-01'));

        $this->assertSame('AB01ABC', $row['RegistrationMark'], 'Registration marks are stored without spaces');
        $this->assertSame('approved', $row['State']);
        $this->assertEquals(2021, $row['SubmissionYear']);
        $this->assertEquals(5, $row['SubmissionMonth']);
        $this->assertEquals($expectedYear, $row['YearNumber']);
        $this->assertEquals($expectedWeek, $row['WeekNumber']);
        $this->assertNull($row['ReasonsForDriverVacancies'], 'An empty list of reasons is exported as NULL');
        $this->assertSame('123.45', $row['AverageBonus'], 'Pence are exported as pounds');
    }

    public function testDomesticDayOfWeek(): void
    {
        $this->loadCoverageFixtures();

        // The survey period starts on Saturday 2021-05-01, so day N is N-1 days later
        $days = $this->connection->fetchAllKeyValue('SELECT DayNumber, DayOfWeek FROM view_domestic_days ORDER BY DayNumber');

        $this->assertNotEmpty($days);
        foreach ($days as $number => $dayOfWeek) {
            $this->assertSame((new \DateTime('2021-05-01'))->modify('+' . ($number - 1) . ' days')->format('D'), $dayOfWeek);
        }
    }

    public function testFeedback(): void
    {
        $this->loadCoverageFixtures();

        $row = $this->fetchOnlyRow('SELECT * FROM view_export_feedback_survey');

        $this->assertSame('domestic', $row['SurveyType']);
        $this->assertSame('AB01ABC', $row['RefOrReg']);
    }

    public function testPreEnquiry(): void
    {
        $this->loadCoverageFixtures();

        $row = $this->fetchOnlyRow('SELECT * FROM view_export_pre_enquiry');

        $this->assertEquals(2021, $row['SubmissionYear']);
        $this->assertEquals(6, $row['SubmissionMonth']);
        $this->assertEquals(1, $row['WasCompanyNameCorrected']);
        $this->assertEquals(70, $row['NumberOfHgvs']);
        $this->assertEquals(30, $row['NumberOfLgvs']);
    }

    public function testRoRoSurvey(): void
    {
        $this->loadCoverageFixtures();

        // Only the closed survey: the new one is excluded
        $row = $this->fetchOnlyRow('SELECT * FROM view_roro_survey');
        $counts = ViewCoverageFixtures::RORO_COUNTS;

        $this->assertEquals([2020, 3, 8], [$row['Year'], $row['Quarter'], $row['Month']]);
        $this->assertSame(['27', '12'], [$row['UKPortCode'], $row['ForeignPortCode']]);
        $this->assertSame('closed', $row['SurveyState']);
        $this->assertSame('bulk', $row['DataEntryMethod'], '"advanced" is exported as "bulk"');
        $this->assertEquals(array_sum($counts), $row['TotalVehicles']);
        $this->assertEquals(array_sum($counts) - $counts['trailers'], $row['TotalPoweredVehicles'], 'Trailers are not powered');
    }

    public function testRoRoVehicleCounts(): void
    {
        $this->loadCoverageFixtures();

        $rows = $this->connection->fetchAllAssociative('SELECT * FROM view_roro_vehicle_count');
        $surveyIds = array_unique(array_column($rows, 'SurveyID'));
        $counts = array_column($rows, 'VehicleCount', 'CountryCode');

        $this->assertCount(1, $surveyIds, 'Only the survey active for its period');
        $this->assertEquals(10, $counts['AT']);
        $this->assertEquals(5, $counts['trailers'], 'Other codes stand in for the country code');
        $this->assertEquals(0, $counts['BY'], 'Counts left blank are exported as 0');
    }

    public function testInternationalFirm(): void
    {
        $this->loadCoverageFixtures();

        $row = $this->fetchOnlyRow('SELECT * FROM view_international_firms');

        $this->assertEquals(150, $row['NumberOfInternationalHGVJourneys']);
        $this->assertEquals(0, $row['NumberOfInternationalLGVJourneys']);
        $this->assertEquals(1, $row['AskedNewLgvQuestions'], 'An LGV answer means the new questions were asked');
        $this->assertEquals(2, $row['NumberOfReminders']);
    }

    /**
     * These tests only read, so the fixtures are loaded once for the class (each load takes a couple of seconds).
     */
    private function loadCoverageFixtures(): void
    {
        if (!self::$fixturesLoaded) {
            $this->databaseTool->loadFixtures([ViewCoverageFixtures::class]);
            self::$fixturesLoaded = true;
        }
    }

    /** @return array<string, mixed> */
    private function fetchOnlyRow(string $sql): array
    {
        $rows = $this->connection->fetchAllAssociative($sql);
        $this->assertCount(1, $rows, $sql);

        return $rows[0];
    }
}
