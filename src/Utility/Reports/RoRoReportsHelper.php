<?php

namespace App\Utility\Reports;

use App\Utility\Reports\DateSeries\YearAndMonth;
use App\Repository\RoRo\SurveyRepository;
use App\Utility\Reports\AbstractReportsHelper;
use App\Utility\StateReportHelper;
use Doctrine\ORM\QueryBuilder;

class RoRoReportsHelper extends AbstractReportsHelper
{
    public function __construct(SurveyRepository $repository, StateReportHelper $stateReportHelper)
    {
        parent::__construct($repository, $stateReportHelper);
    }

    public function getStateReportStats(\DateTime $minStart=null, \DateTime $maxStart=null, array $excludeFromTotals = []): array
    {
        return $this->getStateReportStatsData('s.surveyPeriodStart', new YearAndMonth(), $minStart, $maxStart, $excludeFromTotals);
    }

    #[\Override]
    protected function addDateRelatedGroupAndOrder(QueryBuilder $qb): void
    {
        $qb
            ->addGroupBy('year, month, s.state, s.surveyPeriodStart')
            ->addOrderBy('year, month, s.state');
    }
}