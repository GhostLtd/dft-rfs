<?php

namespace App\Command\Dev\PreEnquiry;

use App\Command\Dev\AbstractTableExportComparator;
use App\Utility\Quarter\NaturalQuarterHelper;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PreEnquiryTableComparator extends AbstractTableExportComparator
{
    public function __construct(SymfonyStyle $io, EntityManagerInterface $entityManager)
    {
        parent::__construct($io, $entityManager);
    }

    public function getExportTableName(): string
    {
        return 'enquiry'; // N.B. AbstractComparisonCommand pulls off the tbl_pre_
    }

    public function getDatabaseIdColumns(): array
    {
        return ['ReferenceNumber', 'DispatchDate'];
    }

    public function getExportIdColumns(): array
    {
        return ['firmRef', 'dispatchDate'];
    }

    public function getRowsFromDatabase(int $year, int $month): Result
    {
        return $this->entityManager->getConnection()
            ->executeQuery(<<<SQL
SELECT s.*
FROM view_export_pre_enquiry AS s
WHERE 
    s.SubmissionYear = :year
    AND s.SubmissionMonth = :month
SQL,
                [
                    'year' => $year,
                    'month' => $month
                ], [
                    'year' => Types::INTEGER,
                    'month' => Types::INTEGER
                ]);
    }

    public function checkRow(array $dbRow, array $exportRow): void
    {
        $this->columnWatcher
            ->columnSeen('PreEnquiryID');

        $this->checkStringColumn('firmRef', $this->getColumn($exportRow, 'firmRef'), $dbRow['ReferenceNumber']);
        $this->checkStringColumn('dispatchDate', $this->getColumn($exportRow, 'dispatchDate'), $dbRow['DispatchDate']);
        $this->checkStringColumn('companyNameDifferent', $this->getColumn($exportRow, 'companyNameDifferent'), $dbRow['WasCompanyNameCorrected']);
        $this->checkStringColumn('companyName', $this->getColumn($exportRow, 'companyName'), $dbRow['CompanyName']);
        $this->checkStringColumn('contactName', $this->getColumn($exportRow, 'contactName'), $dbRow['ContactName']);
        $this->checkStringColumn('contactEmail', $this->getColumn($exportRow, 'contactEmail'), $dbRow['ContactEmail']);
        $this->checkStringColumn('contactPhone', $this->getColumn($exportRow, 'contactPhone'), $dbRow['ContactPhone']);
        $this->checkStringColumn('addressDifferent', $this->getColumn($exportRow, 'addressDifferent'), $dbRow['WasAddressCorrected']);
        $this->checkStringColumn('addressLine1', $this->getColumn($exportRow, 'addressLine1'), $dbRow['AddressLine1']);
        $this->checkStringColumn('addressLine2', $this->getColumn($exportRow, 'addressLine2'), $dbRow['AddressLine2']);
        $this->checkStringColumn('addressLine3', $this->getColumn($exportRow, 'addressLine3'), $dbRow['AddressLine3']);
        $this->checkStringColumn('addressLine4', $this->getColumn($exportRow, 'addressLine4'), $dbRow['AddressLine4']);
        $this->checkStringColumn('addressLine5', $this->getColumn($exportRow, 'addressLine5'), $dbRow['AddressLine5']);
        $this->checkStringColumn('addressPostcode', $this->getColumn($exportRow, 'addressPostcode'), $dbRow['AddressPostcode']);
        $this->checkStringColumn('numberOfHgv', $this->getColumn($exportRow, 'numberOfHgv'), $dbRow['NumberOfHgvs']);
        $this->checkStringColumn('numberOfIntHgv', $this->getColumn($exportRow, 'numberOfIntHgv'), $dbRow['NumberOfHgvsForIntJourneys']);
        $this->checkStringColumn('numberOfIntHgvJourneys', $this->getColumn($exportRow, 'numberOfIntHgvJourneys'), $dbRow['EstimatedNumberOfIntHgvJourneys']);
        $this->checkStringColumn('numberOfLgv', $this->getColumn($exportRow, 'numberOfLgv'), $dbRow['NumberOfLgvs']);
        $this->checkStringColumn('numberOfIntLgv', $this->getColumn($exportRow, 'numberOfIntLgv'), $dbRow['NumberOfLgvsForIntJourneys']);
        $this->checkStringColumn('numberOfIntLgvJourneys', $this->getColumn($exportRow, 'numberOfIntLgvJourneys'), $dbRow['EstimatedNumberOfIntLgvJourneys']);
        $this->checkStringColumn('businessSize', $this->getColumn($exportRow, 'businessSize'), $dbRow['NumberOfEmployees']);
    }
}
