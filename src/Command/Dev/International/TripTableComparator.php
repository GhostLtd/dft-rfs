<?php

namespace App\Command\Dev\International;

use App\Command\Dev\AbstractTableExportComparator;
use App\Entity\Vehicle;
use App\Utility\Quarter\NaturalQuarterHelper;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Types\Types;

class TripTableComparator extends AbstractTableExportComparator
{
    public function getExportTableName(): string
    {
        return 'tblIHRVehicleDetails';
    }

    public function getDatabaseIdColumns(): array
    {
        return ['TripID'];
    }

    public function getExportIdColumns(): array
    {
        return ['IHRVehicleDetailsID'];
    }

    public function getRowsFromDatabase(int $year, int $quarter): Result
    {
        [$minDate, $maxDate] = (new NaturalQuarterHelper())->getDateRangeForYearAndQuarter($year, $quarter);

        return $this->entityManager->getConnection()
            ->executeQuery(<<<SQL
SELECT t.*
FROM view_international_trips AS t
JOIN international_vehicle AS v ON v.id = t.VehicleID
JOIN international_survey_response AS sr ON sr.id = v.survey_response_id
JOIN international_survey AS s ON s.id = sr.survey_id
WHERE
    t.OutboundDate >= :minDate
    AND t.OutboundDate < :maxDate
    AND s.state IN ('closed', 'approved')
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
        $this->checkBitmapColumnMatches($exportRow, $dbRow['OperationType'], [
            Vehicle::OPERATION_TYPE_FOR_HIRE_AND_REWARD => 'ForHireReward',
            Vehicle::OPERATION_TYPE_ON_OWN_ACCOUNT => 'OnOwnAccount',
        ]);

        $this->checkBitmapColumnMatches($exportRow, $dbRow['BodyType'], [
            Vehicle::BODY_TYPE_BOX => 'BoxNonSpecialised',
            Vehicle::BODY_TYPE_CAR => 'CarTansporter',
            Vehicle::BODY_TYPE_CURTAIN_SIDED => 'CurtainSided',
            Vehicle::BODY_TYPE_FLAT_DROP => 'FlatDropSided',
            Vehicle::BODY_TYPE_LIQUID => 'LiquidTanker',
            Vehicle::BODY_TYPE_SOLID_BULK => 'SolidBulkTanker',
            Vehicle::BODY_TYPE_LIVESTOCK => 'LiveStockCarrier',
            Vehicle::BODY_TYPE_TEMPERATURE_CONTROLLED => 'TemperatureControlled',
            Vehicle::BODY_TYPE_TIPPER => 'Tipper',
            Vehicle::BODY_TYPE_OTHER => 'Other',
        ]);

        $this->checkCountriesTransited(
            $this->getColumn($exportRow, 'CountriesTransitted'),
            $dbRow['CountriesTransitedCodes'],
            $dbRow['CountriesTransitedOther'],
        );

        $this->columnWatcher
            ->columnSeen('GrossVehicleWeightNotEntered')
            ->columnSeen('CarryingCapacityNotEntered')
            ->columnSeen('MainlyOperatedNotEntered')
            ->columnSeen('TrailerTypeNotEntered')
            ->columnSeen('SurveyType')
            ->columnSeen('DatesNotEntered')
            ->columnSeen('OutwardJourneyNotEntered')
            ->columnSeen('ReturnJouneyNotEntered')
            ->columnSeen('TripInfoNotEntered')
            ->columnSeen('TotalRoundTripDistance')
            ->columnSeen('RefNumber')
            ->columnSeen('BusinessType')
            ->columnSeen('MilesYN')
            ->columnSeen('KMsYN');

        $this->checkStringColumn('IHRVehicleDetailsID', $this->getColumn($exportRow, 'IHRVehicleDetailsID'), $dbRow['TripID']);
        $this->checkStringColumn('RegMark', $this->getColumn($exportRow, 'RegMark'), $dbRow['RegMark']);
        $this->checkDateColumn('FirstDate', $this->getColumn($exportRow, 'FirstDate'), $dbRow['OutboundDate']);
        $this->checkDateColumn('SecondDate', $this->getColumn($exportRow, 'SecondDate'), $dbRow['ReturnDate']);
        $this->checkStringColumn('GrossVehicleWeight', $this->getColumn($exportRow, 'GrossVehicleWeight'), $dbRow['GrossWeight']);
        $this->checkStringColumn('CarryingCapacity', $this->getColumn($exportRow, 'CarryingCapacity'), $dbRow['CarryingCapacity']);
        $this->checkStringColumn('VehicleOrigin', $this->getColumn($exportRow, 'VehicleOrigin'), $dbRow['Origin'], true);
        $this->checkStringColumn('VehicleDestination', $this->getColumn($exportRow, 'VehicleDestination'), $dbRow['Destination'], true);
        $this->checkStringColumn('UKPortOfDeparture', $this->getColumn($exportRow, 'UKPortOfDeparture'), $dbRow['OutboundUkPort']);
        $this->checkStringColumn('ForeignPortOfArrival', $this->getColumn($exportRow, 'ForeignPortOfArrival'), $dbRow['OutboundForeignPort']);
        $this->checkStringColumn('UKPortOfArrival', $this->getColumn($exportRow, 'UKPortOfArrival'), $dbRow['ReturnUkPort']);
        $this->checkStringColumn('ForeignPortOfDeparture', $this->getColumn($exportRow, 'ForeignPortOfDeparture'), $dbRow['ReturnForeignPort']);
        $this->checkStringColumn('OutwardVehicleEmpty', $this->getColumn($exportRow, 'OutwardVehicleEmpty'), $dbRow['OutboundWasEmpty']);
        $this->checkStringColumn('ReturnVehicleEmpty', $this->getColumn($exportRow, 'ReturnVehicleEmpty'), $dbRow['ReturnWasEmpty']);
        $this->checkStringColumn('Outward90PercentLtd', $this->getColumn($exportRow, 'Outward90PercentLtd'), $dbRow['OutboundWasLimitedBySpace']);
        $this->checkStringColumn('OutwardLtdByWeight', $this->getColumn($exportRow, 'OutwardLtdByWeight'), $dbRow['OutboundWasLimitedByWeight']);
        $this->checkStringColumn('Return90PercentLtd', $this->getColumn($exportRow, 'Return90PercentLtd'), $dbRow['ReturnWasLimitedBySpace']);
        $this->checkStringColumn('ReturnLtdByWeight', $this->getColumn($exportRow, 'ReturnLtdByWeight'), $dbRow['ReturnWasLimitedByWeight']);
        $this->checkStringColumn('AxleConfig', $this->getColumn($exportRow, 'AxleConfig'), $dbRow['AxleConfiguration']);
    }

    protected function checkCountriesTransited(string $exportCountriesTransited, ?string $dbCountriesTransitedCodes, ?string $dbCountriesTransitedOther): void
    {
        $exportCountriesTransited = $exportCountriesTransited === ''
            ? []
            : array_map(fn($x) => trim(strtoupper($x)), explode(',', $exportCountriesTransited));

        foreach($exportCountriesTransited as $idx => $country) {
            foreach($this->countries as $name => $code) {
                if (strtoupper($country) === strtoupper($name)) {
                    $exportCountriesTransited[$idx] = $code;
                    break;
                }
            }
        }
        $exportCountriesTransited = array_unique($exportCountriesTransited);
        sort($exportCountriesTransited);

        $dbCountriesTransitedCodes = $dbCountriesTransitedCodes === null
            ? []
            : array_map(strtoupper(...), json_decode($dbCountriesTransitedCodes, true));

        $dbCountriesTransitedOther = $dbCountriesTransitedOther === null
            ? []
            : array_map(strtoupper(...), json_decode($dbCountriesTransitedOther, true));

        $dbCountriesTransited = array_merge($dbCountriesTransitedCodes, $dbCountriesTransitedOther);
        sort($dbCountriesTransited);

        $this->checkArrayColumn('CountriesTransitted', $exportCountriesTransited, $dbCountriesTransited);
    }
}