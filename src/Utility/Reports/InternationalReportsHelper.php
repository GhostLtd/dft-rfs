<?php

namespace App\Utility\Reports;

use App\Entity\SurveyStateInterface;
use App\Repository\International\SurveyRepository;
use App\Utility\Reports\DateSeries\InternationalWeek;
use App\Utility\Reports\AbstractReportsHelper;
use App\Utility\StateReportHelper;
use Doctrine\ORM\QueryBuilder;

class InternationalReportsHelper extends AbstractReportsHelper
{
    public function __construct(SurveyRepository $repository, StateReportHelper $stateReportHelper)
    {
        parent::__construct($repository, $stateReportHelper);
    }

    public function getStateReportStats(\DateTime $minStart=null, \DateTime $maxStart=null, array $excludeFromTotals = []): array
    {
        return $this->getStateReportStatsData('s.surveyPeriodStart', new InternationalWeek(), $minStart, $maxStart, $excludeFromTotals);
    }

    public function getQualityAssuranceReportStats(\DateTime $minStart=null, \DateTime $maxStart=null): array
    {
        return $this->getQualityAssuranceReportStatsData('s.surveyPeriodStart', new InternationalWeek(), $minStart, $maxStart);
    }

    public function getActivityStatusReportStats(\DateTime $minStart=null, \DateTime $maxStart=null): array
    {
        $dateField = 's.surveyPeriodStart';
        $dateSeriesGenerator = new InternationalWeek();

        $qb = $this->getBaseQueryBuilder($dateSeriesGenerator, $dateField)
            ->addSelect('r.activityStatus')
            ->leftJoin('s.response', 'r')
            ->where('s.state in (:approvedStates)')
            ->setParameter('approvedStates', [
                SurveyStateInterface::STATE_CLOSED,
                SurveyStateInterface::STATE_APPROVED,
            ]);

        $this->addTimeBounds($dateField, $qb, $minStart, $maxStart);
        $this->addDateRelatedGroupAndOrder($qb);

        $results = $qb
            ->addGroupBy("r.activityStatus")
            ->getQuery()
            ->execute();

        $emptyData = [
            'still-active' => 0,
            'ceased-trading' => 0,
            'only-domestic-work' => 0,
        ];

        return $this->generateStats(
            $dateField,
            $dateSeriesGenerator,
            $minStart,
            $maxStart,
            $emptyData,
            $results,
            function(array $result, array &$record) {
                $possessionMode = $result['activityStatus'];
                if ($possessionMode) {
                    $record[$possessionMode] += $result['count'];
                }
            }
        );
    }

    #[\Override]
    protected function addDateRelatedGroupAndOrder(QueryBuilder $qb): void
    {
        $qb
            ->addGroupBy('year, week, s.surveyPeriodStart')
            ->addOrderBy('year, week');
    }
}