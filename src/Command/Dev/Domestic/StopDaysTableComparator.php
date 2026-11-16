<?php

namespace App\Command\Dev\Domestic;

use Doctrine\DBAL\Result;
use Doctrine\DBAL\Types\Types;

class StopDaysTableComparator extends AbstractDaysTableComparator
{
    public function getExportTableName(): string
    {
        return 'fourjourneys';
    }

    public function getExportIdColumns(): array
    {
        return ['QDSurveyDetailsID', 'QDJourneyDetailsFourOrLessID'];
    }

    public function getRowsFromDatabase(int $year, int $quarter): Result
    {
        return $this->entityManager->getConnection()
            ->executeQuery(<<<SQL
SELECT d.*
FROM view_domestic_stop_days AS d
JOIN view_domestic_survey_eligible_with_dates AS s
    ON s.id = d.SurveyID
WHERE 
    s.week_year = :year
    AND s.quarter = :quarter
SQL,
                [
                    'year' => $year,
                    'quarter' => $quarter
                ], [
                    'year' => Types::INTEGER,
                    'quarter' => Types::INTEGER
                ]);
    }

    public function checkRow(array $dbRow, array $exportRow): void
    {
        $this->checkStringColumn('QDJourneyDetailsFourOrLessID', $this->getColumn($exportRow, 'QDJourneyDetailsFourOrLessID'), $dbRow['JourneyID']);

        $this->checkStringColumn('WeightOfGoodsCarriedKG', $this->getColumn($exportRow, 'WeightOfGoodsCarriedKG'), $dbRow['WeightOfGoodsCarried']);
        $this->checkStringColumn('RowNumber', $this->getColumn($exportRow, 'RowNumber'), $dbRow['RowNumber']);
        $this->checkStringColumn('90PercentLtd', $this->getColumn($exportRow, '90PercentLtd'), $dbRow['WasLimitedBySpace']);
        $this->checkStringColumn('LtdByWeight', $this->getColumn($exportRow, 'LtdByWeight'), $dbRow['WasLimitedByWeight']);

        parent::checkRow($dbRow, $exportRow);
    }
}