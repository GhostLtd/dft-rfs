<?php

namespace App\Command\Dev\International;

use App\Command\Dev\AbstractTableExportComparator;
use App\Entity\AbstractGoodsDescription;
use App\Entity\Vehicle;
use App\Utility\Quarter\NaturalQuarterHelper;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Intl\Countries;

class ConsignmentTableComparator extends AbstractTableExportComparator
{
    public function getExportTableName(): string
    {
        return 'tblIHRConsignmentDetails';
    }

    public function getDatabaseIdColumns(): array
    {
        return ['ConsignmentID'];
    }

    public function getExportIdColumns(): array
    {
        return ['IHRConsignmentDetailsID'];
    }

    public function getRowsFromDatabase(int $year, int $quarter): Result
    {
        [$minDate, $maxDate] = (new NaturalQuarterHelper())->getDateRangeForYearAndQuarter($year, $quarter);

        return $this->entityManager->getConnection()
            ->executeQuery(<<<SQL
SELECT c.*, t.id AS TripID
FROM view_international_consignments AS c
JOIN international_trip AS t ON t.id = c.TripID
JOIN international_vehicle AS v ON v.id = t.vehicle_id
WHERE 
    t.outbound_date >= :minDate
    AND t.outbound_date < :maxDate

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
        $tripID = $dbRow['TripID'];

        $this->checkStringColumn('IHRVehicleDetailsID', $this->getColumn($exportRow, 'IHRVehicleDetailsID'), $tripID);
        $this->checkStringColumn('IHRConsignmentDetailsID', $this->getColumn($exportRow, 'IHRConsignmentDetailsID'), $dbRow['ConsignmentID']);
        $this->checkStringColumn('MOA', $this->getColumn($exportRow, 'MOA'), $dbRow['MOA']);
        $this->checkStringColumn('DangerousGoods', $this->getColumn($exportRow, 'DangerousGoods'), $dbRow['DangerousGoodsCode']);
        $this->checkStringColumn('WeightOfGoodsCarriedKG', $this->getColumn($exportRow, 'WeightOfGoodsCarriedKG'), $dbRow['WeightOfGoodsCarried']);
        $this->checkStringColumn('OriginPlace', $this->getColumn($exportRow, 'OriginPlace'), $dbRow['OriginPlace'], true);
        $this->checkStringColumn('OriginCountry', $this->normaliseCountryCode($this->getColumn($exportRow, 'OriginCountry')), $dbRow['OriginCountryCode'] ?? $dbRow['OriginCountryOther']);
        $this->checkStringColumn('DestinationPlace', $this->getColumn($exportRow, 'DestinationPlace'), $dbRow['DestinationPlace'], true);
        $this->checkStringColumn('DestinationCountry', $this->normaliseCountryCode($this->getColumn($exportRow, 'DestinationCountry')), $dbRow['DestinationCountryCode'] ?? $dbRow['DestinationCountryOther']);
        $this->checkStringColumn('LoadedOrder', $this->getColumn($exportRow, 'LoadedOrder'), $dbRow['LoadedOrder']);
        $this->checkStringColumn('UnloadedOrder', $this->getColumn($exportRow, 'UnloadedOrder'), $dbRow['UnloadedOrder']);
        $this->checkStringColumn('RowNumber', $this->getColumn($exportRow, 'RowNumber'), $dbRow['RowNumber']);

        $normalisedDbTypeOfGoods = $dbRow['TypeOfGoods'] !== AbstractGoodsDescription::GOODS_DESCRIPTION_OTHER
            ? $dbRow['TypeOfGoods']
            : $dbRow['TypeOfGoodsOther'];


        $exportTypeOfGoods = $this->getColumn($exportRow, 'TypeOfGoods');

        if ($exportTypeOfGoods === 'Empty') {
            $exportTypeOfGoods = 'empty';
        }

        $this->checkStringColumn('TypeOfGoods', $exportTypeOfGoods, $normalisedDbTypeOfGoods, true);
    }

    protected function normaliseCountryCode(?string $country): ?string
    {
        if ($country === null) {
            return null;
        }

        // The code in TripActionsExportNormalizer doesn't work properly with regards to converting country
        // names -> codes, so we do to do this to be able to compare the output.
        $countryCode = $this->countries[mb_strtoupper($country)] ?? null;

        if ($countryCode !== null) {
            return $countryCode;
        }

        return $country;
    }
}
