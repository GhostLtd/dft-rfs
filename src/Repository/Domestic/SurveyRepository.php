<?php

namespace App\Repository\Domestic;

use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyResponse;
use App\Entity\SurveyInterface;
use App\Repository\DashboardStatsTrait;
use App\Utility\Domestic\WeekNumberHelper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Survey|null find($id, $lockMode = null, $lockVersion = null)
 * @method Survey|null findOneBy(array $criteria, array $orderBy = null)
 * @method Survey[]    findAll()
 * @method Survey[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SurveyRepository extends ServiceEntityRepository
{
    use DashboardStatsTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Survey::class);
    }

    public function findOneByIdWithResponseAndVehicle($id): ?Survey
    {
        return $this->createQueryBuilder('survey')
            ->select('survey, passcode_user, response, vehicle')
            ->leftJoin('survey.passcodeUser', 'passcode_user')
            ->leftJoin('survey.response', 'response')
            ->leftJoin('response.vehicle', 'vehicle')
            ->where('survey.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }


    /**
     * @param bool $isNorthernIreland
     * @return Survey[]
     */
    public function findByTypeWithResponseAndVehicle(bool $isNorthernIreland = false): array
    {
        return $this->createQueryBuilder('survey')
            ->select('survey, passcode_user, response, vehicle')
            ->leftJoin('survey.passcodeUser', 'passcode_user')
            ->leftJoin('survey.response', 'response')
            ->leftJoin('response.vehicle', 'vehicle')
            ->where('survey.isNorthernIreland = :isNI')
            ->setParameter('isNI', $isNorthernIreland)
            ->getQuery()
            ->execute();
    }

    /**
     * @return Survey[]
     */
    public function findForExport($year, $quarter): array
    {
        $dateRange = WeekNumberHelper::getDateRangeForYearAndQuarter($year, $quarter);

        return $this->createQueryBuilder('survey')
            ->select('survey, passcode_user, response, vehicle, days, stops, summary')
            ->leftJoin('survey.passcodeUser', 'passcode_user')
            ->leftJoin('survey.response', 'response')
            ->leftJoin('response.vehicle', 'vehicle')
            ->leftJoin('response.days', 'days')
            ->leftJoin('days.stops', 'stops')
            ->leftJoin('days.summary', 'summary')
            ->where('survey.surveyPeriodStart >= :quarterStart')
            ->andWhere('survey.surveyPeriodStart < :quarterEnd')
            ->andWhere('survey.state = :state')
            ->andWhere('response.isInPossessionOfVehicle IN (:inPossessionCodes)')
            ->orderBy('survey.surveyPeriodStart', 'ASC')
            ->addOrderBy('survey.id', 'ASC')
            ->setParameters([
                'quarterStart' => $dateRange[0],
                'quarterEnd' => $dateRange[1],
                'state' => Survey::STATE_APPROVED,
                'inPossessionCodes' => [SurveyResponse::IN_POSSESSION_YES, SurveyResponse::IN_POSSESSION_SCRAPPED_OR_STOLEN]
            ])
            ->getQuery()
            ->execute();
    }

    public function getOverdueCount(bool $isNortherIreland): int
    {
        return $this->createQueryBuilder('s')
            ->select('count(s) AS count')
            ->where('s.isNorthernIreland = :isNorthernIreland')
            ->andWhere('s.surveyPeriodEnd < :now')
            ->andWhere('s.state IN (:activeStates)')
            ->setParameters([
                'now' => new \DateTime(),
                'activeStates' => SurveyInterface::ACTIVE_STATES,
                'isNorthernIreland' => $isNortherIreland,
            ])
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getInProgressCount(bool $isNortherIreland): int
    {
        return $this->createQueryBuilder('s')
            ->select('count(s) AS count')
            ->where('s.isNorthernIreland = :isNorthernIreland')
            ->andWhere('s.state IN (:activeStates)')
            ->setParameters([
                'activeStates' => SurveyInterface::ACTIVE_STATES,
                'isNorthernIreland' => $isNortherIreland,
            ])
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getStateReportStats(?bool $isNorthernIreland = null, ?\DateTime $minStart = null, ?\DateTime $maxStart = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->select('s.state, count(s) AS count, MIN(s.surveyPeriodStart) AS surveyPeriodStart, WEEK(s.surveyPeriodStart) AS week');

        $this->addTimeAndLocationBounds($qb, $isNorthernIreland, $minStart, $maxStart);

        $results = $qb
            ->groupBy('s.state, week')
            ->orderBy('week, s.state')
            ->getQuery()
            ->execute();

        [$firstWeek, $firstYear, $lastWeek, $lastYear] = $this->getWeekAndYearRange($results, $minStart, $maxStart);

        $mergeStates = [
            SurveyInterface::STATE_NEW => SurveyInterface::STATE_INVITATION_SENT,
            SurveyInterface::STATE_INVITATION_PENDING => SurveyInterface::STATE_INVITATION_SENT,
            SurveyInterface::STATE_EXPORTING => SurveyInterface::STATE_EXPORTED,
            SurveyInterface::STATE_INVITATION_FAILED => SurveyInterface::STATE_REJECTED,
        ];

        $stats = [];
        for ($year = $firstYear; $year <= $lastYear; $year++) {
            $stats[$year] = ['data' => []];
            for ($week = $firstWeek; $week <= $lastWeek; $week++) {
                $stats[$year]['data'][$week] = [
                    'data' => [
                        SurveyInterface::STATE_INVITATION_SENT => 0,
                        SurveyInterface::STATE_IN_PROGRESS => 0,
                        SurveyInterface::STATE_CLOSED => 0,
                        SurveyInterface::STATE_REJECTED => 0,
                        SurveyInterface::STATE_EXPORTED => 0,
                        SurveyInterface::STATE_APPROVED => 0,
                    ],
                ];
            }
        }

        foreach ($results as $result) {
            $dateTime = new \DateTime($result['surveyPeriodStart']);
            [$week, $year] = WeekNumberHelper::getWeekNumberAndYear($dateTime);
            $state = $result['state'];

            if (array_key_exists($state, $mergeStates)) {
                $state = $mergeStates[$state];
            }

            $stats[$year]['data'][$week]['data'][$state] = $result['count'];
        }

        return $this->addTotalsAndFlags($stats);
    }

    public function getMinimumAndMaximumYear(): array
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

    public function getQualityAssuranceReportStats(?bool $isNorthernIreland = null, ?\DateTime $minStart = null, ?\DateTime $maxStart = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->select('s.qualityAssured, count(s) AS count, MIN(s.surveyPeriodStart) AS surveyPeriodStart, WEEK(s.surveyPeriodStart) AS week')
            ->where('s.state in (:approvedStates)')
            ->setParameter('approvedStates', [
                SurveyInterface::STATE_APPROVED,
                SurveyInterface::STATE_EXPORTING,
                SurveyInterface::STATE_EXPORTED,
            ]);

        $this->addTimeAndLocationBounds($qb, $isNorthernIreland, $minStart, $maxStart);

        $results = $qb
            ->groupBy('s.qualityAssured, week')
            ->orderBy('week')
            ->getQuery()
            ->execute();

        [$firstWeek, $firstYear, $lastWeek, $lastYear] = $this->getWeekAndYearRange($results, $minStart, $maxStart);

        $stats = [];
        for ($year = $firstYear; $year <= $lastYear; $year++) {
            $stats[$year] = ['data' => []];
            for ($week = $firstWeek; $week <= $lastWeek; $week++) {
                $stats[$year]['data'][$week] = [
                    'data' => [
                        'quality-assured' => 0,
                        'not-quality-assured' => 0,
                    ],
                ];
            }
        }

        foreach ($results as $result) {
            $dateTime = new \DateTime($result['surveyPeriodStart']);
            [$week, $year] = WeekNumberHelper::getWeekNumberAndYear($dateTime);
            $qualityAssured = $result['qualityAssured'] == 1 ? 'quality-assured' : 'not-quality-assured';

            $stats[$year]['data'][$week]['data'][$qualityAssured] = $result['count'];
        }

        return $this->addTotalsAndFlags($stats);
    }

    public function getPossessionReportStats(?bool $isNorthernIreland = null, ?\DateTime $minStart = null, ?\DateTime $maxStart = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->select('r.isInPossessionOfVehicle, count(s) AS count, MIN(s.surveyPeriodStart) AS surveyPeriodStart, WEEK(s.surveyPeriodStart) AS week')
            ->leftJoin('s.response', 'r')
            ->where('s.state in (:closedStates)')
            ->setParameter('closedStates', [
                SurveyInterface::STATE_CLOSED,
                SurveyInterface::STATE_APPROVED,
                SurveyInterface::STATE_REJECTED,
                SurveyInterface::STATE_EXPORTING,
                SurveyInterface::STATE_EXPORTED,
            ]);

        $this->addTimeAndLocationBounds($qb, $isNorthernIreland, $minStart, $maxStart);

        $results = $qb
            ->groupBy('r.isInPossessionOfVehicle, week')
            ->orderBy('week')
            ->getQuery()
            ->execute();

        [$firstWeek, $firstYear, $lastWeek, $lastYear] = $this->getWeekAndYearRange($results, $minStart, $maxStart);

        $stats = [];
        for ($year = $firstYear; $year <= $lastYear; $year++) {
            $stats[$year] = ['data' => []];
            for ($week = $firstWeek; $week <= $lastWeek; $week++) {
                $stats[$year]['data'][$week] = [
                    'data' => [
                        SurveyResponse::IN_POSSESSION_ON_HIRE => 0,
                        SurveyResponse::IN_POSSESSION_SOLD => 0,
                        SurveyResponse::IN_POSSESSION_SCRAPPED_OR_STOLEN => 0,
                        SurveyResponse::IN_POSSESSION_YES => 0,
                    ],
                ];
            }
        }

        foreach ($results as $result) {
            $dateTime = new \DateTime($result['surveyPeriodStart']);
            [$week, $year] = WeekNumberHelper::getWeekNumberAndYear($dateTime);
            $possessionMode = $result['isInPossessionOfVehicle'];

            $stats[$year]['data'][$week]['data'][$possessionMode] = $result['count'];
        }

        return $this->addTotalsAndFlags($stats);
    }

    protected function addTimeAndLocationBounds(QueryBuilder $qb, ?bool $isNorthernIreland, ?\DateTime $minStart, ?\DateTime $maxStart): QueryBuilder
    {
        if ($isNorthernIreland !== null) {
            $qb
                ->andWhere('s.isNorthernIreland = :isNorthernIreland')
                ->setParameter('isNorthernIreland', $isNorthernIreland);
        }

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

    protected function getWeekAndYearRange($results, ?\DateTime $minStart, ?\DateTime $maxStart): array
    {
        if ($minStart !== null && $maxStart !== null) {
            [$firstWeek, $firstYear] = WeekNumberHelper::getWeekNumberAndYear($minStart);
            [$lastWeek, $lastYear] = WeekNumberHelper::getWeekNumberAndYear($maxStart);

            if ($lastWeek === 1) {
                $lastWeek = 53;
                $lastYear--;
            } else {
                $lastWeek--;
            }
        } else {
            [$firstWeek, $firstYear] = WeekNumberHelper::getWeekNumberAndYear($results[0]['surveyPeriodStart']);
            [$lastWeek, $lastYear] = WeekNumberHelper::getWeekNumberAndYear($results[count($results) - 1]['surveyPeriodStart']);
        }

        return [$firstWeek, $firstYear, $lastWeek, $lastYear];
    }

    protected function addTotalsAndFlags(array $stats): array
    {
        foreach ($stats as $year => $yearsStats) {
            $totals = [];
            $sumOfTotals = 0;
            foreach ($yearsStats['data'] as $week => $weekStats) {
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
                $stats[$year]['data'][$week]['total'] = $total;
                $stats[$year]['data'][$week]['allZeros'] = $allZeros;
                $sumOfTotals += $total;
            }
            $stats[$year]['totals'] = $totals;
            $stats[$year]['total'] = $sumOfTotals;
        }

        return $stats;
    }
}
