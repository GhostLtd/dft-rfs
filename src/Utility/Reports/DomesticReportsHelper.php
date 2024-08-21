<?php

namespace App\Utility\Reports;

use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyResponse;
use App\Entity\SurveyStateInterface;
use App\Repository\Domestic\SurveyRepository;
use App\Utility\Reports\DateSeries\YearAndWeek;
use App\Utility\Reports\AbstractReportsHelper;
use App\Utility\StateReportHelper;
use Doctrine\ORM\QueryBuilder;

class DomesticReportsHelper extends AbstractReportsHelper
{
    public function __construct(SurveyRepository $repository, StateReportHelper $stateReportHelper)
    {
        parent::__construct($repository, $stateReportHelper);
    }

    public function getStateReportStats(bool $isNorthernIreland = null, \DateTime $minStart=null, \DateTime $maxStart=null, array $excludeFromTotals = []): array
    {
        return $this->getStateReportStatsData('s.surveyPeriodStart', new YearAndWeek(), $minStart, $maxStart, $excludeFromTotals, fn(QueryBuilder $qb) => $this->addNorthernIrelandBounds($qb, $isNorthernIreland));
    }

    public function getQualityAssuranceReportStats(bool $isNorthernIreland = null, \DateTime $minStart=null, \DateTime $maxStart=null): array
    {
        return $this->getQualityAssuranceReportStatsData('s.surveyPeriodStart', new YearAndWeek(), $minStart, $maxStart, fn(QueryBuilder $qb) => $this->addNorthernIrelandBounds($qb, $isNorthernIreland));
    }

    public function getPossessionReportStats(bool $isNorthernIreland = null, \DateTime $minStart=null, \DateTime $maxStart=null): array
    {
        $dateField = 's.surveyPeriodStart';
        $dateSeriesGenerator = new YearAndWeek();

        $qb = $this->getBaseQueryBuilder($dateSeriesGenerator, $dateField)
            ->addSelect('r.isInPossessionOfVehicle')
            ->leftJoin('s.response', 'r')
            ->where('s.state in (:closedStates)')
            ->setParameter('closedStates', [
                SurveyStateInterface::STATE_CLOSED,
                SurveyStateInterface::STATE_APPROVED,
                SurveyStateInterface::STATE_EXPORTING,
                SurveyStateInterface::STATE_EXPORTED,
                Survey::STATE_REISSUED,
            ]);

        $this->addTimeBounds($dateField, $qb, $minStart, $maxStart);
        $this->addDateRelatedGroupAndOrder($qb);
        $this->addNorthernIrelandBounds($qb, $isNorthernIreland);

        $results = $qb
            ->addGroupBy("r.isInPossessionOfVehicle")
            ->getQuery()
            ->execute();

        $emptyData = [
            SurveyResponse::IN_POSSESSION_ON_HIRE => 0,
            SurveyResponse::IN_POSSESSION_SOLD => 0,
            SurveyResponse::IN_POSSESSION_SCRAPPED_OR_STOLEN => 0,
            SurveyResponse::IN_POSSESSION_YES => 0,
        ];

        return $this->generateStats(
            $dateField,
            $dateSeriesGenerator,
            $minStart,
            $maxStart,
            $emptyData,
            $results,
            function(array $result, array &$record) {
                $possessionMode = $result['isInPossessionOfVehicle'];

                if ($possessionMode) {
                    $record[$possessionMode] += $result['count'];
                }
            }
        );
    }

    protected function addNorthernIrelandBounds(QueryBuilder $qb, ?bool $isNorthernIreland): QueryBuilder
    {
        if ($isNorthernIreland !== null) {
            $qb
                ->andWhere('s.isNorthernIreland = :isNorthernIreland')
                ->setParameter('isNorthernIreland', $isNorthernIreland);
        }

        return $qb;
    }

    #[\Override]
    protected function addDateRelatedGroupAndOrder(QueryBuilder $qb): void
    {
        $qb
            ->addGroupBy('year, week, s.surveyPeriodStart')
            ->addOrderBy('year, week');
    }
}