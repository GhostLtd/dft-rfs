<?php

namespace App\Repository\PreEnquiry;

use App\Entity\PreEnquiry\PreEnquiry;
use App\Entity\SurveyInterface;
use App\Repository\DashboardStatsTrait;
use App\Utility\StateReportHelper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use RuntimeException;

/**
 * @method PreEnquiry|null find($id, $lockMode = null, $lockVersion = null)
 * @method PreEnquiry|null findOneBy(array $criteria, array $orderBy = null)
 * @method PreEnquiry[]    findAll()
 * @method PreEnquiry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PreEnquiryRepository extends ServiceEntityRepository
{
    use DashboardStatsTrait;

    protected StateReportHelper $stateReportHelper;

    public function __construct(ManagerRegistry $registry, StateReportHelper $stateReportHelper)
    {
        parent::__construct($registry, PreEnquiry::class);
        $this->stateReportHelper = $stateReportHelper;
    }

    public function findLatestSurveyForTesting(): ?PreEnquiry
    {
        try {
            return $this->createQueryBuilder('pe')
                ->orderBy('pe.id', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            throw new RuntimeException('Query with maxResults 1 returned multiple results!');
        }
    }

    /**
     */
    public function findForExportMonth(int $year, int $month): array
    {
        $monthStart = \DateTimeImmutable::createFromFormat('Y-m-d', "$year-$month-1")->modify('midnight first day of this month');
        $monthEnd = $monthStart->modify('+1 month');

        return $this->createQueryBuilder('pe')
            ->select('pe, per')
            ->leftJoin('pe.response', 'per')
            ->where('pe.state = :state')
            ->andWhere('pe.submissionDate >= :monthStart')
            ->andWhere('pe.submissionDate < :monthEnd')
            ->setParameters([
                'state' => SurveyInterface::STATE_CLOSED,
                'monthStart' => $monthStart,
                'monthEnd' => $monthEnd,
            ])
            ->getQuery()
            ->execute();
    }

    public function getMinimumAndMaximumYear(): array
    {
        $result = $this->createQueryBuilder('p')
            ->select('MIN(YEAR(p.dispatchDate)) AS min, MAX(YEAR(p.dispatchDate)) AS max')
            ->getQuery()
            ->getArrayResult();

        if (count($result) === 0) {
            $year = (new \DateTime())->format('Y');
            return [$year, $year];
        } else {
            $firstResult = current($result);
            return array_values($firstResult);
        }
    }

    public function getStateReportStats(?\DateTime $minStart = null, ?\DateTime $maxStart = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p.state, count(p) AS count, p.dispatchDate AS dispatchDate, MONTH(p.dispatchDate) AS month, YEAR(p.dispatchDate) AS year');

        $this->addTimeBounds($qb, $minStart, $maxStart);

        $results = $qb
            ->groupBy('year, month, p.state, dispatchDate')
            ->orderBy('year, month, p.state')
            ->getQuery()
            ->execute();

        $mergeStates = $this->stateReportHelper->getStateReportMergeMappings();

        $stats = [];
        for ($month = 1; $month <= 12; $month++) {
            $stats['data'][$month] = [
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
            $date = ($result['dispatchDate']);
            $month = intval($date->format('n'));
            $state = $result['state'];

            if (array_key_exists($state, $mergeStates)) {
                $state = $mergeStates[$state];
            }

            if (!isset($stats['data'][$month]['data'][$state])) {
                $stats['data'][$month]['data'][$state] = 0;
            }

            $stats['data'][$month]['data'][$state] += $result['count'];
        }

        return $this->addTotalsAndFlags($stats);
    }

    // TODO: Refactor / pull this out (+ others)
    protected function addTotalsAndFlags(array $stats): array
    {
        $totals = [];
        $sumOfTotals = 0;
        foreach ($stats['data'] as $month => $monthStats) {
            $allZeros = true;
            $total = 0;
            foreach ($monthStats['data'] as $name => $data) {
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
            $stats['data'][$month]['total'] = $total;
            $stats['data'][$month]['allZeros'] = $allZeros;
            $sumOfTotals += $total;
        }
        $stats['totals'] = $totals;
        $stats['total'] = $sumOfTotals;
        return $stats;
    }

    protected function addTimeBounds(QueryBuilder $qb, ?\DateTime $minStart, ?\DateTime $maxStart): QueryBuilder
    {
        if ($minStart !== null) {
            $qb
                ->andWhere('p.dispatchDate >= :minStart')
                ->setParameter('minStart', $minStart);
        }

        if ($maxStart !== null) {
            $qb
                ->andWhere('p.dispatchDate < :maxStart')
                ->setParameter('maxStart', $maxStart);
        }

        return $qb;
    }
}
