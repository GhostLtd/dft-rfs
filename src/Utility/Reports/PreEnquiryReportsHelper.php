<?php

namespace App\Utility\Reports;

use App\Repository\PreEnquiry\PreEnquiryRepository;
use App\Utility\Reports\DateSeries\YearAndMonth;
use App\Utility\Reports\AbstractReportsHelper;
use App\Utility\StateReportHelper;
use Doctrine\ORM\QueryBuilder;

class PreEnquiryReportsHelper extends AbstractReportsHelper
{
    public function __construct(PreEnquiryRepository $repository, StateReportHelper $stateReportHelper)
    {
        parent::__construct($repository, $stateReportHelper);
    }

    public function getStateReportStats(\DateTime $minStart=null, \DateTime $maxStart=null, array $excludeFromTotals = []): array
    {
        return $this->getStateReportStatsData('s.dispatchDate', new YearAndMonth(), $minStart, $maxStart, $excludeFromTotals);
    }

    #[\Override]
    protected function addDateRelatedGroupAndOrder(QueryBuilder $qb): void
    {
        $qb
            ->groupBy('year, month, s.state, s.dispatchDate')
            ->orderBy('year, month, s.state');
    }
}