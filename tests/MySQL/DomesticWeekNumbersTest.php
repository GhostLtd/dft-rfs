<?php

namespace App\Tests\MySQL;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\SurveyStateInterface;
use App\Tests\DataFixtures\UserFixtures;
use App\Utility\Domestic\WeekNumberHelper as DomesticWeekNumberHelper;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * view_domestic_survey_eligible_with_dates numbers weeks in SQL (Monday-based week years), and every domestic export
 * view inherits its week_year/week_number/quarter. They must agree with the PHP the rest of the app uses, especially
 * around the turn of the year.
 */
class DomesticWeekNumbersTest extends KernelTestCase
{
    use RunsOnMySql;

    public static function dataDomesticWeekNumbers(): array
    {
        return [
            'mid-year' => ['2021-05-01'],
            'monday 28th december' => ['2020-12-28'],
            'friday 1st january' => ['2021-01-01'],
            'monday 4th january' => ['2021-01-04'],
            'monday 30th december' => ['2024-12-30'],
            'wednesday 1st january' => ['2025-01-01'],
            'monday 29th december' => ['2025-12-29'],
            'thursday 1st january' => ['2026-01-01'],
            'sunday 3rd january' => ['2027-01-03'],
            'thursday 31st december' => ['2026-12-31'],
        ];
    }

    #[DataProvider('dataDomesticWeekNumbers')]
    public function testDomesticWeekNumbersMatchThePhpHelper(string $periodStart): void
    {
        static::bootKernel();
        $container = static::getContainer();
        $container->get(DatabaseToolCollection::class)->get()->loadFixtures([UserFixtures::class]);
        $entityManager = $container->get(EntityManagerInterface::class);
        $connection = $container->get('doctrine.dbal.default_connection');

        $survey = (new DomesticSurvey())
            ->setSurveyPeriodStart(new \DateTime($periodStart))
            ->setSurveyPeriodEnd((new \DateTime($periodStart))->modify('+6 days'))
            ->setIsNorthernIreland(false)
            ->setRegistrationMark('WK01 ABC')
            ->setState(SurveyStateInterface::STATE_NEW);
        $entityManager->persist($survey);
        $entityManager->flush();

        $rows = $connection->fetchAllAssociative('SELECT week_year, week_number, quarter FROM view_domestic_survey_eligible_with_dates');
        $this->assertCount(1, $rows);
        $row = $rows[0];
        [$expectedWeek, $expectedYear] = DomesticWeekNumberHelper::getYearlyWeekNumberAndYear(new \DateTime($periodStart));

        $this->assertEquals([$expectedYear, $expectedWeek], [$row['week_year'], $row['week_number']]);
        $this->assertEquals(min(4, intdiv($expectedWeek - 1, 13) + 1), $row['quarter']);
    }

}
