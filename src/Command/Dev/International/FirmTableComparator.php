<?php

namespace App\Command\Dev\International;

use App\Command\Dev\AbstractTableExportComparator;
use App\Utility\Quarter\NaturalQuarterHelper;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class FirmTableComparator extends AbstractTableExportComparator
{
    public function __construct(SymfonyStyle $io, EntityManagerInterface $entityManager)
    {
        parent::__construct($io, $entityManager);
    }


    public function getExportTableName(): string
    {
        return 'tblIHRFirmDetails';
    }

    public function getDatabaseIdColumns(): array
    {
        return ['SurveyID'];
    }

    public function getExportIdColumns(): array
    {
        return ['id'];
    }

    public function getRowsFromDatabase(int $year, int $quarter): Result
    {
        [$minDate, $maxDate] = (new NaturalQuarterHelper())->getDateRangeForYearAndQuarter($year, $quarter);
        return $this->entityManager->getConnection()
            ->executeQuery(<<<SQL
SELECT f.*
FROM view_international_firms AS f
WHERE 
    f.SurveyPeriodEnd >= :minDate
    AND f.SurveyPeriodStart < :maxDate
SQL,
                [
                    'minDate' => $minDate,
                    'maxDate' => $maxDate
                ], [
                    'minDate' => Types::DATETIME_MUTABLE,
                    'maxDate' => Types::DATETIME_MUTABLE
                ]);
    }

    public function checkRow(array $dbRow, array $exportRow): void
    {
        $this->columnWatcher
            ->columnSeen('qa')
            ->columnSeen('contactName')
            ->columnSeen('contactEmail')
            ->columnSeen('contactPhone');

        $this->checkStringColumn('id', $this->getColumn($exportRow, 'id'), $dbRow['SurveyID']);
        $this->checkStringColumn('refNumber', $this->getColumn($exportRow, 'refNumber'), $dbRow['ReferenceNumber']);
        $this->checkStringColumn('status', $this->getColumn($exportRow, 'status'), $dbRow['SurveyStatus']);
        $this->checkStringColumn('reminders', $this->getColumn($exportRow, 'reminders'), $dbRow['NumberOfReminders']);
        $this->checkStringColumn('numberOfIntJourneys', $this->getColumn($exportRow, 'numberOfIntJourneys'), $dbRow['NumberOfInternationalJourneys']);
        $this->checkStringColumn('activityStatus', $this->getColumn($exportRow, 'activityStatus'), $dbRow['ActivityStatus']);
        $this->checkStringColumn('reasonForEmptySurvey', $this->getColumn($exportRow, 'reasonForEmptySurvey'), $dbRow['ReasonForEmptySurvey']);
        $this->checkStringColumn('reasonForEmptySurveyOther', $this->getColumn($exportRow, 'reasonForEmptySurveyOther'), $dbRow['ReasonForEmptySurveyOther'], true);
        $this->checkStringColumn('businessNature', $this->getColumn($exportRow, 'businessNature'), $dbRow['BusinessNature'], true);
        $this->checkStringColumn('businessSize', $this->getColumn($exportRow, 'businessSize'), $dbRow['NumberOfEmployees']);
    }
}
