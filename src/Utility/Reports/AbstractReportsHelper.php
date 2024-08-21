<?php

namespace App\Utility\Reports;

use App\Entity\Domestic\Survey;
use App\Entity\SurveyStateInterface;
use App\Utility\Reports\DateSeries\DateSeriesGeneratorInterface;
use App\Utility\StateReportHelper;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

abstract class AbstractReportsHelper
{
    public function __construct(
        protected EntityRepository $repository,
        protected StateReportHelper $stateReportHelper
    ) {}

    protected function getBaseQueryBuilder(DateSeriesGeneratorInterface $dateSeriesGenerator, string $dateField): QueryBuilder
    {
        // N.B. Although we use the WEEK() function here, we're not relying upon it to generate week numbers, as those
        //      are generated later via a call to a WeekNumberHelper method. The WEEK() here purely serves to make sure
        //      data is grouped into batches of weeks that start on a Monday.
        $qb = $this->repository
            ->createQueryBuilder('s')
            ->select('count(s) AS count');

        return $dateSeriesGenerator->addToQueryBuilder($qb, $dateField);
    }

    protected function getStateReportStatsData(
        string $dateField,
        DateSeriesGeneratorInterface $dateSeriesGenerator,
        \DateTime $minStart = null,
        \DateTime $maxStart = null,
        array $excludeFromTotals = [],
        \Closure $queryBuilderDecorator = null,
    ): array
    {
        $qb = $this->getBaseQueryBuilder($dateSeriesGenerator, $dateField)
                ->addSelect('s.state');

        $this->addTimeBounds($dateField, $qb, $minStart, $maxStart);
        $this->addDateRelatedGroupAndOrder($qb);

        $queryBuilderDecorator?->call($this, $qb);

        $results = $qb
            ->addGroupBy('s.state')
            ->addOrderBy('s.state')
            ->getQuery()
            ->execute();

        $emptyData = [
            SurveyStateInterface::STATE_INVITATION_SENT => 0,
            SurveyStateInterface::STATE_IN_PROGRESS => 0,
            SurveyStateInterface::STATE_CLOSED => 0,
            SurveyStateInterface::STATE_REJECTED => 0,
            SurveyStateInterface::STATE_EXPORTED => 0,
            SurveyStateInterface::STATE_APPROVED => 0,
            Survey::STATE_REISSUED => 0,
        ];

        $mergeStates = $this->stateReportHelper->getStateReportMergeMappings();

        return $this->generateStats(
            $dateField,
            $dateSeriesGenerator,
            $minStart,
            $maxStart,
            $emptyData,
            $results,
            function(array $result, array &$record) use ($mergeStates) {
                $state = $result['state'];

                if (array_key_exists($state, $mergeStates)) {
                    $state = $mergeStates[$state];
                }

                if ($state) {
                    $record[$state] += $result['count'];
                }
            },
            $excludeFromTotals
        );
    }

    protected function getQualityAssuranceReportStatsData(
        string $dateField,
        DateSeriesGeneratorInterface $dateSeriesGenerator,
        \DateTime $minStart=null,
        \DateTime $maxStart=null,
        \Closure $queryBuilderDecorator=null
    ): array
    {
        $qb = $this->getBaseQueryBuilder($dateSeriesGenerator, $dateField)
            ->addSelect('s.qualityAssured')
            ->where('s.state in (:approvedStates)')
            ->setParameter('approvedStates', [
                SurveyStateInterface::STATE_APPROVED,
                SurveyStateInterface::STATE_EXPORTING,
                SurveyStateInterface::STATE_EXPORTED,
            ]);

        $this->addTimeBounds($dateField, $qb, $minStart, $maxStart);
        $this->addDateRelatedGroupAndOrder($qb);

        $queryBuilderDecorator?->call($this, $qb);

        $results = $qb
            ->addGroupBy('s.qualityAssured')
            ->getQuery()
            ->execute();

        $emptyData = [
            'quality-assured' => 0,
            'not-quality-assured' => 0,
        ];

        return $this->generateStats(
            $dateField,
            $dateSeriesGenerator,
            $minStart,
            $maxStart,
            $emptyData,
            $results,
            function(array $result, array &$record) {
                $qualityAssured = $result['qualityAssured'] == 1 ? 'quality-assured' : 'not-quality-assured';
                $record[$qualityAssured] += $result['count'];
            }
        );
    }

    protected function addTimeBounds(string $dateField, QueryBuilder $qb, ?\DateTime $minStart, ?\DateTime $maxStart): QueryBuilder
    {
        if ($minStart !== null) {
            $qb
                ->andWhere("{$dateField} >= :minStart")
                ->setParameter('minStart', $minStart);
        }

        if ($maxStart !== null) {
            $qb
                ->andWhere("{$dateField} < :maxStart")
                ->setParameter('maxStart', $maxStart);
        }

        return $qb;
    }

    public function generateStats(
        string                       $dateField,
        DateSeriesGeneratorInterface $dateSeriesGenerator,
        ?\DateTime                   $minStart,
        ?\DateTime                   $maxStart,
        array                        $emptyData,
        array                        $results,
        \Closure                     $recordCallback,
        array                        $excludeFromTotals = [],
    ): array
    {
        $stats = ['data' => []];

        // 1. Generate empty stats structure

        /** @var array<int|string> $rangeParts */
        foreach ($dateSeriesGenerator->dateRangeGenerator($minStart, $maxStart) as $rangeParts) {
            $current = &$stats['data'];
            $lastKey = array_key_last($rangeParts);
            foreach ($rangeParts as $key => $part) {
                $current[$part] ??= ['data' => []];

                if ($key === $lastKey) {
                    $current[$part]['data'] = $emptyData;
                } else {
                    $current = &$current[$part]['data'];
                }
            }
        }

        // 2. Add results

        // For each result, we descend into stats to find the record, and then call callback(result, record),
        // allowing the callback to alter the stats record based on the results from the database.

        if (preg_match('/^(?:[a-z]+\.)?(.*)$/i', $dateField, $matches)) {
            // String any prefix off of the dateField (e.g. s.surveyStart -> surveyStart)
            $dateField = $matches[1];
        }

        foreach ($results as $result) {
            $record = &$stats['data'];
            foreach($dateSeriesGenerator->dateToParts($result[$dateField]) as $part) {
                $record = &$record[$part]['data'];
            }

            $recordCallback($result, $record);
        }

        // 3. Add totals / flags
        return $this->addTotalsAndFlags($stats, $excludeFromTotals);
    }

    protected function addTotalsAndFlags(array $stats, array $excludeFromTotals=[]): array
    {
        $totals = [];

        $firstDataKey = array_key_first($stats['data']);
        $isLeaf = $firstDataKey === null || !is_array($stats['data'][$firstDataKey]);

        if ($isLeaf) {
            $total = 0;

            foreach($stats['data'] as $key => $count) {
                if (!in_array($key, $excludeFromTotals)) {
                    $total += $count;
                }
            }
        } else {
            $total = 0;

            foreach($stats['data'] as $key => $data) {
                $stats['data'][$key] = $this->addTotalsAndFlags($data, $excludeFromTotals);
                $total += $stats['data'][$key]['total'];
            }

            foreach($stats['data'] as $data) {
                $dataOrTotals = array_key_exists('totals', $data) ?
                    $data['totals'] :
                    $data['data'];

                foreach($dataOrTotals as $dataKey => $dataValue) {
                    $totals[$dataKey] ??= 0;
                    $totals[$dataKey] += $dataValue;
                }
            }

            $stats['totals'] = $totals;
        }

        $stats['total'] = $total;
        $stats['allZeros'] = $total === 0;
        return $stats;
    }

    abstract protected function addDateRelatedGroupAndOrder(QueryBuilder $qb): void;
}