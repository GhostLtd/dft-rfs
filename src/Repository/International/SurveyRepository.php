<?php

namespace App\Repository\International;

use App\Entity\International\Company;
use App\Entity\International\Survey;
use App\Entity\SurveyInterface;
use App\Repository\AbstractSurveyRepository;
use App\Repository\DashboardStatsTrait;
use App\Utility\International\WeekNumberHelper;
use App\Utility\StateReportHelper;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Survey|null find($id, $lockMode = null, $lockVersion = null)
 * @method Survey|null findOneBy(array $criteria, array $orderBy = null)
 * @method Survey[]    findAll()
 * @method Survey[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SurveyRepository extends AbstractSurveyRepository
{
    protected CompanyRepository $companyRepo;
    protected EntityManagerInterface $entityManager;
    protected StateReportHelper $stateReportHelper;

    use DashboardStatsTrait;

    public function __construct(ManagerRegistry $registry, StateReportHelper $stateReportHelper)
    {
        $this->stateReportHelper = $stateReportHelper;
        $this->entityManager = $registry->getManager();
        $this->companyRepo = $this->entityManager->getRepository(Company::class);

        parent::__construct($registry, Survey::class);
    }

    public function findWithVehiclesAndTrips(string $id): ?Survey
    {
        return $this->createQueryBuilder('s')
            ->select('s, r, v, t, a, p')
            ->leftJoin('s.response', 'r')
            ->leftJoin('r.vehicles', 'v')
            ->leftJoin('v.trips', 't')
            ->leftJoin('t.actions', 'a')
            ->leftJoin('s.passcodeUser', 'p')
            ->where('s.id = :id')
            ->setParameters([
                'id' => $id,
            ])
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getOverdueCount(): int
    {
        return $this->createQueryBuilder('s')
            ->select('count(s) AS count')
            ->where('s.surveyPeriodEnd < :now')
            ->andWhere('s.state IN (:activeStates)')
            ->setParameter('now', new \DateTime())
            ->setParameter('activeStates', SurveyInterface::ACTIVE_STATES)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getInProgressCount(): int
    {
        return $this->createQueryBuilder('s')
            ->select('count(s) AS count')
            ->where('s.state IN (:activeStates)')
            ->setParameter('activeStates', SurveyInterface::ACTIVE_STATES)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getStateReportStats(?\DateTime $minStart = null, ?\DateTime $maxStart = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->select('s.state, count(s) AS count, s.surveyPeriodStart AS surveyPeriodStart, WEEK(s.surveyPeriodStart, 0) AS week, YEAR(s.surveyPeriodStart) AS year');

        $this->addTimeBounds($qb, $minStart, $maxStart);

        $results = $qb
            ->groupBy('year, week, s.state, week, surveyPeriodStart')
            ->orderBy('year, week, s.state')
            ->getQuery()
            ->execute();

        [$firstWeek, $lastWeek] = $this->getWeekRange($minStart, $maxStart, $results);

        $mergeStates = $this->stateReportHelper->getStateReportMergeMappings();

        $stats = [];
        for ($week = $firstWeek; $week <= $lastWeek; $week++) {
            $stats['data'][$week] = [
                'data' => [
                    SurveyInterface::STATE_INVITATION_SENT => 0,
                    SurveyInterface::STATE_IN_PROGRESS => 0,
                    SurveyInterface::STATE_CLOSED => 0,
                    SurveyInterface::STATE_REJECTED => 0,
//                    SurveyInterface::STATE_EXPORTED => 0,
                    SurveyInterface::STATE_APPROVED => 0,
                ],
            ];
        }

        foreach ($results as $result) {
            $week = WeekNumberHelper::getWeekNumber($result['surveyPeriodStart']);
            $state = $result['state'];

            if (array_key_exists($state, $mergeStates)) {
                $state = $mergeStates[$state];
            }

            if (!isset($stats['data'][$week]['data'][$state])) {
                $stats['data'][$week]['data'][$state] = 0;
            }

            $stats['data'][$week]['data'][$state] += $result['count'];
        }

        return $this->addTotalsAndFlags($stats);
    }


    public function getQualityAssuranceReportStats(?\DateTime $minStart = null, ?\DateTime $maxStart = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->select('s.qualityAssured, count(s) AS count, s.surveyPeriodStart AS surveyPeriodStart, WEEK(s.surveyPeriodStart, 0) AS week, YEAR(s.surveyPeriodStart) AS year')
            ->where('s.state in (:approvedStates)')
            ->setParameter('approvedStates', [
                SurveyInterface::STATE_APPROVED,
                SurveyInterface::STATE_EXPORTING,
                SurveyInterface::STATE_EXPORTED,
            ]);

        $this->addTimeBounds($qb, $minStart, $maxStart);

        $results = $qb
            ->groupBy('s.qualityAssured, s.surveyPeriodStart, year, week')
            ->orderBy('year, week')
            ->getQuery()
            ->execute();

        [$firstWeek, $lastWeek] = $this->getWeekRange($minStart, $maxStart, $results);


        $stats = [];
        for ($week = $firstWeek; $week <= $lastWeek; $week++) {
            $stats['data'][$week] = [
                'data' => [
                    'quality-assured' => 0,
                    'not-quality-assured' => 0,
                ],
            ];
        }

        foreach ($results as $result) {
            $week = WeekNumberHelper::getWeekNumber($result['surveyPeriodStart']);
            $qualityAssured = $result['qualityAssured'] == 1 ? 'quality-assured' : 'not-quality-assured';

            if (!isset($stats['data'][$week]['data'][$qualityAssured])) {
                $stats['data'][$week]['data'][$qualityAssured] = 0;
            }

            $stats['data'][$week]['data'][$qualityAssured] += $result['count'];
        }

        return $this->addTotalsAndFlags($stats);
    }

    public function getActivityStatusReportStats(?\DateTime $minStart = null, ?\DateTime $maxStart = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->select(<<<EOQ
    r.activityStatus, 
    count(s) AS count, 
    MIN(s.surveyPeriodStart) AS surveyPeriodStart, 
    WEEK(s.surveyPeriodStart, 0) AS week, 
    YEAR(s.surveyPeriodStart) AS year
EOQ
)
            ->where('s.state in (:approvedStates)')
            ->setParameter('approvedStates', [
                SurveyInterface::STATE_CLOSED,
                SurveyInterface::STATE_APPROVED,
                SurveyInterface::STATE_EXPORTING,
                SurveyInterface::STATE_EXPORTED,
            ]);

        $this->addTimeBounds($qb, $minStart, $maxStart);

        $results = $qb
            ->innerJoin('s.response', 'r') // Ignore surveys that haven't had a response - e.g. Licence revoked
            ->groupBy('r.activityStatus, year, week')
            ->orderBy('year, week')
            ->getQuery()
            ->execute();

        [$firstWeek, $lastWeek] = $this->getWeekRange($minStart, $maxStart, $results);

        $stats = [];
        for ($week = $firstWeek; $week <= $lastWeek; $week++) {
            $stats['data'][$week] = [
                'data' => [
                    'still-active' => 0,
                    'ceased-trading' => 0,
                    'only-domestic-work' => 0,
                ],
            ];
        }

        foreach ($results as $result) {
            $week = WeekNumberHelper::getWeekNumber(new \DateTime($result['surveyPeriodStart']));
            $activityStatus = $result['activityStatus'];

            if (!isset($stats['data'][$week]['data'][$activityStatus])) {
                $stats['data'][$week]['data'][$activityStatus] = 0;
            }

            $stats['data'][$week]['data'][$activityStatus] += $result['count'];
        }

        return $this->addTotalsAndFlags($stats);
    }

    public function getMinimumAndMaximumYear()
    {
        $result = $this->createQueryBuilder('s')
            ->select('MIN(YEAR(s.surveyPeriodStart)) AS min, MAX(YEAR(s.surveyPeriodStart)) AS max')
            ->getQuery()
            ->getArrayResult();

        if (count($result) === 0) {
            $year = (new \DateTime())->format('Y');
            return [$year, $year];
        } else {
            return array_values(current($result));
        }
    }

    protected function addTimeBounds(QueryBuilder $qb, ?DateTime $minStart, ?DateTime $maxStart): QueryBuilder
    {
        if ($minStart !== null) {
            $qb
                ->andWhere('s.surveyPeriodStart >= :minStart')
                ->setParameter('minStart', $minStart);
        }

        if ($maxStart !== null) {
            $qb
                ->andWhere('s.surveyPeriodStart < :maxStart')
                ->setParameter('maxStart', $maxStart);
        }

        return $qb;
    }

    protected function getWeekRange(?DateTime $minStart, ?DateTime $maxStart, $results): array
    {
        if ($minStart !== null && $maxStart !== null) {
            $firstWeek = WeekNumberHelper::getWeekNumber($minStart);
            $lastWeek = WeekNumberHelper::getWeekNumber($maxStart);
            $lastWeek--;
        } else {
            $firstWeek = WeekNumberHelper::getWeekNumber($results[0]['surveyPeriodStart']);
            $lastWeek = WeekNumberHelper::getWeekNumber($results[count($results) - 1]['surveyPeriodStart']);
        }

        return [$firstWeek, $lastWeek];
    }

    protected function addTotalsAndFlags(array $stats): array
    {
        $totals = [];
        $sumOfTotals = 0;
        foreach ($stats['data'] as $week => $weekStats) {
            $allZeros = true;
            $total = 0;
            foreach ($weekStats['data'] as $name => $data) {
                $total += $data;
                if ($data > 0) {
                    $allZeros = false;
                }

                if (!isset($totals[$name])) {
                    $totals[$name] = $data;
                } else {
                    $totals[$name] += $data;
                }
            }
            $stats['data'][$week]['total'] = $total;
            $stats['data'][$week]['allZeros'] = $allZeros;
            $sumOfTotals += $total;
        }
        $stats['totals'] = $totals;
        $stats['total'] = $sumOfTotals;
        return $stats;
    }

    /**
     * @return Survey[]|array
     */
    public function getSurveysRequiringPasscodeUserCleanup(\DateTime $before): array
    {
        return $this->doGetSurveysRequiringPasscodeUserCleanup($before, 'international_survey');
    }

    /**
     * @return Survey[]|array
     */
    public function getSurveysRequiringPersonalDataCleanup(\DateTime $before): array {
        $states = [SurveyInterface::STATE_REJECTED, SurveyInterface::STATE_EXPORTED];
        return $this->doGetSurveysRequiringPersonalDataCleanup($before, $states, 'international_survey');
    }

    public function getSurveysForExport(DateTime $minDate, DateTime $maxDate): array
    {
        return $this->createQueryBuilder('s')
            ->select('s, r, v, t, p')
            ->leftJoin('s.response', 'r')
            ->leftJoin('r.vehicles', 'v')
            ->leftJoin('v.trips', 't')
            // If we don't join on user, it does n+1 queries
            ->leftJoin('s.passcodeUser', 'p')
            ->andWhere('s.surveyPeriodEnd >= :minDate')
            ->andWhere('s.surveyPeriodEnd < :maxDate')
            ->getQuery()
            ->setParameters([
                'minDate' => $minDate,
                'maxDate' => $maxDate,
            ])
            ->execute();
    }
}
