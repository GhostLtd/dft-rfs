<?php

namespace App\Command\Dev\Domestic;

use Doctrine\DBAL\Result;
use Doctrine\DBAL\Types\Types;

class SummaryDaysTableComparator extends AbstractDaysTableComparator
{
    public function getExportTableName(): string
    {
        return 'fivejourneys';
    }

    public function getExportIdColumns(): array
    {
        return ['QDSurveyDetailsID', 'QDJournerysFiveorMoreID'];
    }

    public function getRowsFromDatabase(int $year, int $quarter): Result
    {
        return $this->entityManager->getConnection()
            ->executeQuery(<<<SQL
SELECT d.*, s.registration_mark AS RegistrationMark
FROM view_domestic_summary_days AS d
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
        $this->checkStringColumn('QDJournerysFiveorMoreID', $this->getColumn($exportRow, 'QDJournerysFiveorMoreID'), $dbRow['JourneyID']);

        $this->checkStringColumn('RegMark', $this->getColumn($exportRow, 'RegMark'), $dbRow['RegistrationMark']);
        $this->checkStringColumn('WeightOfGoodsCollected', $this->getColumn($exportRow, 'WeightOfGoodsCollected'), $dbRow['WeightOfGoodsCollected']);
        $this->checkStringColumn('WeightOfGoodsDelivered', $this->getColumn($exportRow, 'WeightOfGoodsDelivered'), $dbRow['WeightOfGoodsDelivered']);
        $this->checkStringColumn('NumberOfStopsForCollection', $this->getColumn($exportRow, 'NumberOfStopsForCollection'), $dbRow['NumberOfStopsForCollection']);
        $this->checkStringColumn('NumberOfStopsForDelivery', $this->getColumn($exportRow, 'NumberOfStopsForDelivery'), $dbRow['NumberOfStopsForDelivery']);
        $this->checkStringColumn('NumberOfStopsForBoth', $this->getColumn($exportRow, 'NumberOfStopsForBoth'), $dbRow['NumberOfStopsForBoth']);
        $this->checkStringColumn('FurthestStop', $this->getColumn($exportRow, 'FurthestStop'), $dbRow['FurthestStop'], true);

        parent::checkRow($dbRow, $exportRow);
    }
}