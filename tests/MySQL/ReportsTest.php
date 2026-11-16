<?php

namespace App\Tests\MySQL;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\Domestic\SurveyResponse as DomesticSurveyResponse;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\SurveyStateInterface;
use App\Tests\DataFixtures\International\ResponseStillActiveFixtures;
use App\Tests\DataFixtures\PreEnquiry\SurveyFixtures as PreEnquirySurveyFixtures;
use App\Tests\DataFixtures\RoRo\SurveyFixtures as RoRoSurveyFixtures;
use App\Utility\Reports\DateRangeHelper;
use App\Utility\Reports\DateSeries\DateSeriesGeneratorInterface;
use App\Utility\Reports\DateSeries\InternationalWeek;
use App\Utility\Reports\DateSeries\YearAndMonth;
use App\Utility\Reports\DateSeries\YearAndWeek;
use App\Utility\Reports\DomesticReportsHelper;
use App\Utility\Reports\InternationalReportsHelper;
use App\Utility\Reports\PreEnquiryReportsHelper;
use App\Utility\Reports\RoRoReportsHelper;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The admin reports' queries use MySQL-only DQL (WEEK(), YEAR(), MONTH()) and raw SQL, so they can't run on SQLite.
 * They also run under production's ONLY_FULL_GROUP_BY here. The rows are grouped by date in SQL and bucketed into
 * weeks/months in PHP, so these check the counts that reach each bucket.
 */
class ReportsTest extends KernelTestCase
{
    use RunsOnMySql;

    private ContainerInterface $container;
    private EntityManagerInterface $entityManager;

    #[\Override]
    protected function setUp(): void
    {
        static::bootKernel();
        $this->container = static::getContainer();
        $this->container->get(DatabaseToolCollection::class)->get()->loadFixtures([
            ResponseStillActiveFixtures::class, // international survey 2021-05-01, in progress, still active
            PreEnquirySurveyFixtures::class,    // dispatched 2021-06-01, new
            RoRoSurveyFixtures::class,          // two surveys for 2020-08, new
        ]);
        $this->entityManager = $this->container->get(EntityManagerInterface::class);
    }

    public function testDomesticReports(): void
    {
        // Mon 3rd, Tue 4th and Wed 5th May 2021 fall in one week; Mon 10th in the next
        $this->createDomesticSurvey('2021-05-03', SurveyStateInterface::STATE_APPROVED, DomesticSurveyResponse::IN_POSSESSION_YES, qualityAssured: true);
        $this->createDomesticSurvey('2021-05-04', SurveyStateInterface::STATE_APPROVED, DomesticSurveyResponse::IN_POSSESSION_SOLD, northernIreland: true);
        $this->createDomesticSurvey('2021-05-05', SurveyStateInterface::STATE_CLOSED, DomesticSurveyResponse::IN_POSSESSION_ON_HIRE);
        $this->createDomesticSurvey('2021-05-10', SurveyStateInterface::STATE_IN_PROGRESS);
        $this->entityManager->flush();

        $helper = $this->container->get(DomesticReportsHelper::class);
        [$min, $max] = [new \DateTime('2021-05-03'), new \DateTime('2021-05-17')];
        $weekOne = new \DateTime('2021-05-03');
        $weekTwo = new \DateTime('2021-05-10');
        $series = new YearAndWeek();

        $states = $helper->getStateReportStats(null, $min, $max);
        $this->assertBucket(['approved' => 2, 'closed' => 1, 'in-progress' => 0], $states, $series, $weekOne);
        $this->assertBucket(['approved' => 0, 'in-progress' => 1], $states, $series, $weekTwo);

        $greatBritainStates = $helper->getStateReportStats(false, $min, $max);
        $this->assertBucket(['approved' => 1, 'closed' => 1], $greatBritainStates, $series, $weekOne);

        $qualityAssurance = $helper->getQualityAssuranceReportStats(null, $min, $max);
        $this->assertBucket(['quality-assured' => 1, 'not-quality-assured' => 1], $qualityAssurance, $series, $weekOne);

        $possession = $helper->getPossessionReportStats(null, $min, $max);
        $this->assertBucket([
            DomesticSurveyResponse::IN_POSSESSION_YES => 1,
            DomesticSurveyResponse::IN_POSSESSION_SOLD => 1,
            DomesticSurveyResponse::IN_POSSESSION_ON_HIRE => 1,
            DomesticSurveyResponse::IN_POSSESSION_SCRAPPED_OR_STOLEN => 0,
        ], $possession, $series, $weekOne);
    }

    public function testInternationalReports(): void
    {
        // The activity status report only counts closed/approved surveys
        $this->entityManager->getRepository(InternationalSurvey::class)
            ->findOneBy(['referenceNumber' => '101010-5059'])
            ->setState(SurveyStateInterface::STATE_CLOSED);
        $this->entityManager->flush();

        $helper = $this->container->get(InternationalReportsHelper::class);
        [$min, $max] = [new \DateTime('2021-04-26'), new \DateTime('2021-05-17')];
        $surveyWeek = new \DateTime('2021-05-01');
        $series = new InternationalWeek();

        $this->assertBucket(['closed' => 1, 'in-progress' => 0], $helper->getStateReportStats($min, $max), $series, $surveyWeek);
        $this->assertBucket(['still-active' => 1, 'ceased-trading' => 0], $helper->getActivityStatusReportStats($min, $max), $series, $surveyWeek);
    }

    public function testPreEnquiryReport(): void
    {
        $stats = $this->container->get(PreEnquiryReportsHelper::class)
            ->getStateReportStats(new \DateTime('2021-05-01'), new \DateTime('2021-08-01'));

        $this->assertBucket([SurveyStateInterface::STATE_INVITATION_SENT => 1], $stats, new YearAndMonth(), new \DateTime('2021-06-01'));
        $this->assertBucket([SurveyStateInterface::STATE_INVITATION_SENT => 0], $stats, new YearAndMonth(), new \DateTime('2021-07-01'));
    }

    public function testRoRoReport(): void
    {
        $stats = $this->container->get(RoRoReportsHelper::class)
            ->getStateReportStats(new \DateTime('2020-07-01'), new \DateTime('2020-10-01'));

        $this->assertBucket([SurveyStateInterface::STATE_INVITATION_SENT => 2], $stats, new YearAndMonth(), new \DateTime('2020-08-01'));
    }

    public function testDateRangeCoversEverySurveyType(): void
    {
        $this->createDomesticSurvey('2023-01-02', SurveyStateInterface::STATE_NEW);
        $this->entityManager->flush();

        // RoRo 2020 is the earliest, domestic 2023 the latest. The helper swallows database errors (it returned
        // nothing on SQLite, which has no YEAR()), so this is the only place it really runs.
        $this->assertEquals([2020, 2023], $this->container->get(DateRangeHelper::class)->getMinAndMaxYearsForAllSurveys());
    }

    private function createDomesticSurvey(string $periodStart, string $state, ?string $possession = null, bool $qualityAssured = false, bool $northernIreland = false): void
    {
        $survey = (new DomesticSurvey())
            ->setSurveyPeriodStart(new \DateTime($periodStart))
            ->setSurveyPeriodEnd((new \DateTime($periodStart))->modify('+6 days'))
            ->setIsNorthernIreland($northernIreland)
            ->setRegistrationMark('RP01 ' . substr(md5($periodStart), 0, 3))
            ->setState($state)
            ->setQualityAssured($qualityAssured);

        if ($possession !== null) {
            $response = (new DomesticSurveyResponse())->setIsInPossessionOfVehicle($possession);
            $survey->setResponse($response);
            $this->entityManager->persist($response);
        }

        $this->entityManager->persist($survey);
    }

    /** Descends the report structure by the date's parts (as the report does) and checks the given counts */
    private function assertBucket(array $expected, array $stats, DateSeriesGeneratorInterface $series, \DateTime $date): void
    {
        $bucket = $stats;
        foreach ($series->dateToParts($date) as $part) {
            $this->assertArrayHasKey($part, $bucket['data'], "No bucket for {$date->format('Y-m-d')}");
            $bucket = $bucket['data'][$part];
        }

        $this->assertEquals($expected, array_intersect_key($bucket['data'], $expected), "Bucket for {$date->format('Y-m-d')}");
    }
}
