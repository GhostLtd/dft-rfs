<?php

namespace App\Repository\International;

use App\Entity\International\Company;
use App\Entity\International\Survey;
use App\Entity\SurveyInterface;
use App\Repository\DashboardStatsTrait;
use App\Utility\International\WeekNumberHelper;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
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
    protected CompanyRepository $companyRepo;
    protected EntityManagerInterface $entityManager;

    use DashboardStatsTrait;

    public function __construct(ManagerRegistry $registry)
    {
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

    /**
     * @return Survey[]|Collection
     */
    public function getSurveysForExport(DateTime $weekStart, DateTime $weekEnd): array
    {
        return $this->createQueryBuilder('s')
            ->select('s,r,v,t,a')
            ->leftJoin('s.response', 'r')
            ->leftJoin('r.vehicles', 'v')
            ->leftJoin('v.trips', 't')
            ->leftJoin('t.actions', 'a')
            ->leftJoin('a.loadingAction', 'la')
            ->where('s.state = :state')
            ->andWhere('s.surveyPeriodStart >= :weekStart')
            ->andWhere('s.surveyPeriodStart < :weekEnd')
            ->orderBy('s.surveyPeriodStart', 'ASC')
            ->addOrderBy('v.registrationMark', 'ASC')
            ->addOrderBy('a.number', 'ASC')
            ->getQuery()
            ->setParameters([
                'state' => Survey::STATE_APPROVED,
                'weekStart' => $weekStart,
                'weekEnd' => $weekEnd,
            ])
            ->execute();
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
            ->select('s.state, count(s) AS count, MIN(s.surveyPeriodStart) as surveyPeriodStart, WEEK(s.surveyPeriodStart) AS week');

        $this->addTimeBounds($qb, $minStart, $maxStart);

        $results = $qb
            ->groupBy('s.state, week')
            ->orderBy('week, s.state')
            ->getQuery()
            ->execute();

        [$firstWeek, $lastWeek] = $this->getWeekRange($minStart, $maxStart, $results);

        $mergeStates = [
            SurveyInterface::STATE_NEW => SurveyInterface::STATE_INVITATION_SENT,
            SurveyInterface::STATE_INVITATION_PENDING => SurveyInterface::STATE_INVITATION_SENT,
            SurveyInterface::STATE_EXPORTING => SurveyInterface::STATE_EXPORTED,
            SurveyInterface::STATE_INVITATION_FAILED => SurveyInterface::STATE_REJECTED,
        ];

        $stats = [];
        for ($week = $firstWeek; $week <= $lastWeek; $week++) {
            $stats['data'][$week] = [
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

        foreach ($results as $result) {
            $dateTime = new \DateTime($result['surveyPeriodStart']);
            $week = WeekNumberHelper::getWeekNumber($dateTime);
            $state = $result['state'];

            if (array_key_exists($state, $mergeStates)) {
                $state = $mergeStates[$state];
            }

            $stats['data'][$week]['data'][$state] = $result['count'];
        }

        return $this->addTotalsAndFlags($stats);
    }


    public function getQualityAssuranceReportStats(?\DateTime $minStart = null, ?\DateTime $maxStart = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->select('s.qualityAssured, count(s) AS count, MIN(s.surveyPeriodStart), WEEK(s.surveyPeriodStart) AS week')
            ->where('s.state in (:approvedStates)')
            ->setParameter('approvedStates', [
                SurveyInterface::STATE_APPROVED,
                SurveyInterface::STATE_EXPORTING,
                SurveyInterface::STATE_EXPORTED,
            ]);

        $this->addTimeBounds($qb, $minStart, $maxStart);

        $results = $qb
            ->groupBy('s.qualityAssured, week')
            ->orderBy('week')
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
            $dateTime = new \DateTime($result['surveyPeriodStart']);
            $week = WeekNumberHelper::getWeekNumber($dateTime);
            $qualityAssured = $result['qualityAssured'] == 1 ? 'quality-assured' : 'not-quality-assured';

            $stats['data'][$week]['data'][$qualityAssured] = $result['count'];
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
}
