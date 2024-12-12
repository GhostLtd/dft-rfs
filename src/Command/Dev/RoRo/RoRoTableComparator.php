<?php

namespace App\Command\Dev\RoRo;

use App\Command\Dev\AbstractTableExportComparator;
use App\Utility\Quarter\NaturalQuarterHelper;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RoRoTableComparator extends AbstractTableExportComparator
{
    public function __construct(SymfonyStyle $io, EntityManagerInterface $entityManager)
    {
        parent::__construct($io, $entityManager);
    }

    public function getExportTableName(): string
    {
        return 'roro'; // N.B. AbstractComparisonCommand pulls off the tbl_
    }

    public function getDatabaseIdColumns(): array
    {
        return ['Year', 'Month', 'RoRoNo'];
    }

    public function getExportIdColumns(): array
    {
        return ['Year', 'Month', 'RoRoNo'];
    }

    public function getRowsFromDatabase(int $year, int $month): Result
    {
        return $this->entityManager->getConnection()
            ->executeQuery(<<<SQL
SELECT s.*
FROM view_export_roro_survey AS s
WHERE 
    s.year = :year
    AND s.month = :month
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
            ->columnSeen('SurveyID');

        $this->checkStringColumn('Year', $this->getColumn($exportRow, 'Year'), $dbRow['Year']);
        $this->checkStringColumn('Quarter', $this->getColumn($exportRow, 'Quarter'), $dbRow['Quarter']);
        $this->checkStringColumn('Month', $this->getColumn($exportRow, 'Month'), $dbRow['Month']);
        $this->checkStringColumn('RoRoNo', $this->getColumn($exportRow, 'RoRoNo'), $dbRow['RoRoNo']);
        $this->checkStringColumn('OperatorId', $this->getColumn($exportRow, 'OperatorId'), $dbRow['OperatorCode']);
        $this->checkStringColumn('IsActiveForMonth', $this->getColumn($exportRow, 'IsActiveForMonth'), $dbRow['IsActiveForMonth']);
        $this->checkStringColumn('SurveyState', $this->getColumn($exportRow, 'SurveyState'), $dbRow['SurveyState']);
        $this->checkStringColumn('AL', $this->getColumn($exportRow, 'AL'), $dbRow['AL']);
        $this->checkStringColumn('AT', $this->getColumn($exportRow, 'AT'), $dbRow['AT']);
        $this->checkStringColumn('BA', $this->getColumn($exportRow, 'BA'), $dbRow['BA']);
        $this->checkStringColumn('BE', $this->getColumn($exportRow, 'BE'), $dbRow['BE']);
        $this->checkStringColumn('BG', $this->getColumn($exportRow, 'BG'), $dbRow['BG']);
        $this->checkStringColumn('BY', $this->getColumn($exportRow, 'BY'), $dbRow['BY']);
        $this->checkStringColumn('CH', $this->getColumn($exportRow, 'CH'), $dbRow['CH']);
        $this->checkStringColumn('CY', $this->getColumn($exportRow, 'CY'), $dbRow['CY']);
        $this->checkStringColumn('CZ', $this->getColumn($exportRow, 'CZ'), $dbRow['CZ']);
        $this->checkStringColumn('DE', $this->getColumn($exportRow, 'DE'), $dbRow['DE']);
        $this->checkStringColumn('DK', $this->getColumn($exportRow, 'DK'), $dbRow['DK']);
        $this->checkStringColumn('EE', $this->getColumn($exportRow, 'EE'), $dbRow['EE']);
        $this->checkStringColumn('ES', $this->getColumn($exportRow, 'ES'), $dbRow['ES']);
        $this->checkStringColumn('FI', $this->getColumn($exportRow, 'FI'), $dbRow['FI']);
        $this->checkStringColumn('FR', $this->getColumn($exportRow, 'FR'), $dbRow['FR']);
        $this->checkStringColumn('GB', $this->getColumn($exportRow, 'GB'), $dbRow['GB']);
        $this->checkStringColumn('GE', $this->getColumn($exportRow, 'GE'), $dbRow['GE']);
        $this->checkStringColumn('GR', $this->getColumn($exportRow, 'GR'), $dbRow['GR']);
        $this->checkStringColumn('HR', $this->getColumn($exportRow, 'HR'), $dbRow['HR']);
        $this->checkStringColumn('HU', $this->getColumn($exportRow, 'HU'), $dbRow['HU']);
        $this->checkStringColumn('IE', $this->getColumn($exportRow, 'IE'), $dbRow['IE']);
        $this->checkStringColumn('IS', $this->getColumn($exportRow, 'IS'), $dbRow['IS']);
        $this->checkStringColumn('IT', $this->getColumn($exportRow, 'IT'), $dbRow['IT']);
        $this->checkStringColumn('LT', $this->getColumn($exportRow, 'LT'), $dbRow['LT']);
        $this->checkStringColumn('LU', $this->getColumn($exportRow, 'LU'), $dbRow['LU']);
        $this->checkStringColumn('LV', $this->getColumn($exportRow, 'LV'), $dbRow['LV']);
        $this->checkStringColumn('MD', $this->getColumn($exportRow, 'MD'), $dbRow['MD']);
        $this->checkStringColumn('ME', $this->getColumn($exportRow, 'ME'), $dbRow['ME']);
        $this->checkStringColumn('MK', $this->getColumn($exportRow, 'MK'), $dbRow['MK']);
        $this->checkStringColumn('MT', $this->getColumn($exportRow, 'MT'), $dbRow['MT']);
        $this->checkStringColumn('NL', $this->getColumn($exportRow, 'NL'), $dbRow['NL']);
        $this->checkStringColumn('NO', $this->getColumn($exportRow, 'NO'), $dbRow['NO']);
        $this->checkStringColumn('PL', $this->getColumn($exportRow, 'PL'), $dbRow['PL']);
        $this->checkStringColumn('PT', $this->getColumn($exportRow, 'PT'), $dbRow['PT']);
        $this->checkStringColumn('RO', $this->getColumn($exportRow, 'RO'), $dbRow['RO']);
        $this->checkStringColumn('RS', $this->getColumn($exportRow, 'RS'), $dbRow['RS']);
        $this->checkStringColumn('RU', $this->getColumn($exportRow, 'RU'), $dbRow['RU']);
        $this->checkStringColumn('SE', $this->getColumn($exportRow, 'SE'), $dbRow['SE']);
        $this->checkStringColumn('SI', $this->getColumn($exportRow, 'SI'), $dbRow['SI']);
        $this->checkStringColumn('SK', $this->getColumn($exportRow, 'SK'), $dbRow['SK']);
        $this->checkStringColumn('TR', $this->getColumn($exportRow, 'TR'), $dbRow['TR']);
        $this->checkStringColumn('UA', $this->getColumn($exportRow, 'UA'), $dbRow['UA']);
        $this->checkStringColumn('XK', $this->getColumn($exportRow, 'XK'), $dbRow['XK']);
        $this->checkStringColumn('OtherCountries', $this->getColumn($exportRow, 'OtherCountries'), $dbRow['OtherCountries']);
        $this->checkStringColumn('Unknown', $this->getColumn($exportRow, 'Unknown'), $dbRow['Unknown']);
        $this->checkStringColumn('TotalPoweredVehicles', $this->getColumn($exportRow, 'TotalPoweredVehicles'), $dbRow['TotalPoweredVehicles']);
        $this->checkStringColumn('UnaccompaniedTrailers', $this->getColumn($exportRow, 'UnaccompaniedTrailers'), $dbRow['UnaccompaniedTrailers']);
        $this->checkStringColumn('TotalVehicles', $this->getColumn($exportRow, 'TotalVehicles'), $dbRow['TotalVehicles']);
        $this->checkStringColumn('UKPort', $this->getColumn($exportRow, 'UKPort'), $dbRow['UKPortCode']);
        $this->checkStringColumn('ForeignPort', $this->getColumn($exportRow, 'ForeignPort'), $dbRow['ForeignPortCode']);
        $this->checkStringColumn('Comments', $this->getColumn($exportRow, 'Comments'), $dbRow['Comments'], true);
        $this->checkStringColumn('IsApproved', $this->getColumn($exportRow, 'IsApproved'), $dbRow['SurveyState'] === 'approved' ? 1 : 0);
        $this->checkStringColumn('DataEntryMethod', $this->getColumn($exportRow, 'DataEntryMethod'), $dbRow['DataEntryMethod']);
    }
}
