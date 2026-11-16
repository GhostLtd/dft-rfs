<?php

namespace App\Command\Dev\Domestic;

use App\Command\Dev\AbstractTableExportComparator;
use App\Entity\Domestic\SurveyResponse;
use App\Entity\Vehicle;
use Doctrine\DBAL\Result;

class VehicleTableComparator extends AbstractTableExportComparator
{
    public function getExportTableName(): string
    {
        return 'vehicles';
    }

    public function getDatabaseIdColumns(): array
    {
        return ['SurveyID'];
    }

    public function getExportIdColumns(): array
    {
        return ['QDSurveyDetailsID'];
    }

    public function getRowsFromDatabase(int $year, int $quarter): Result
    {
        return $this->entityManager->getConnection()
            ->executeQuery(<<<SQL
SELECT v.*, su_cnt.summary_count AS summary_count, st_cnt.stop_count AS stop_count
FROM view_domestic_survey_vehicles AS v
LEFT JOIN (
  SELECT SurveyID, COUNT(DISTINCT JourneyID) AS summary_count
  FROM view_domestic_summary_days
  GROUP BY SurveyID
) AS su_cnt ON su_cnt.SurveyID = v.SurveyID
LEFT JOIN (
  SELECT SurveyID, COUNT(DISTINCT JourneyID) AS stop_count
  FROM view_domestic_stop_days
  GROUP BY SurveyID
) AS st_cnt ON st_cnt.SurveyID = v.SurveyID
WHERE 
    WeekBasedYear = :year
    AND WeekBasedQuarter = :quarter
SQL,
                [
                    'year' => $year,
                    'quarter' => $quarter
                ]);
    }

    public function checkRow(array $dbRow, array $exportRow): void
    {
        $this->columnWatcher
            ->columnSeen('CarryingCapacityNotEntered')
            ->columnSeen('ContactEmail') // DfT specified should not be exported
            ->columnSeen('ContactName') // DfT specified should not be exported
            ->columnSeen('ContactTelNo') // DfT specified should not be exported
            ->columnSeen('DateCompleted')
            ->columnSeen('DateInput')
            ->columnSeen('DateSurveySent')
            ->columnSeen('DateReminder1')
            ->columnSeen('DateReminder2')
            ->columnSeen('DisableReminders')
            ->columnSeen('Discard') // Redundant - constant
            ->columnSeen('HireCompany') // DfT specified should not be exported
            ->columnSeen('KMsEmptyYN')
            ->columnSeen('KMsLoadedYN')
            ->columnSeen('MilesEmptyYN')
            ->columnSeen('MilesLoadedYN')
            ->columnSeen('KMsEmptyYN5Plus')
            ->columnSeen('KMsLoadedYN5Plus')
            ->columnSeen('MilesEmptyYN5Plus')
            ->columnSeen('MilesLoadedYN5Plus')
            ->columnSeen('MainlyOperatedNotEntered')
            ->columnSeen('ScrappedStolen') // Redundant - PossessionState = 'scrapped-or-stolen'
            ->columnSeen('SendEMail')
            ->columnSeen('ReturnCodeID')
            ->columnSeen('Sold') // Redundant - PossessionState = 'sold'
            ->columnSeen('SurveyAddressLine1')
            ->columnSeen('SurveyAddressLine2')
            ->columnSeen('SurveyAddressLine3')
            ->columnSeen('SurveyAddressLine4')
            ->columnSeen('SurveyAddressLine5')
            ->columnSeen('SurveyEmail')
            ->columnSeen('SurveyWeek')
            ->columnSeen('TickBoxForSortingRecords')
            ->columnSeen('TrailerTypeNotEntered')
            ->columnSeen('TripDataLoaded')
            ->columnSeen('WithTrips') // Redundant - same as "not NotUsed" or "journeyCount > 0"
        ;

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

        $this->checkStringColumn('QDSurveyDetailsID', $this->getColumn($exportRow, 'QDSurveyDetailsID'), $dbRow['SurveyID']);
        $this->checkStringColumn('SurveyState', $this->getColumn($exportRow, 'SurveyState'), $dbRow['SurveyState']);
        $this->checkStringColumn('RegMark', $this->getColumn($exportRow, 'RegMark'), $dbRow['RegistrationMark']);
        $this->checkStringColumn('CarryingCapacity', $this->getColumn($exportRow, 'CarryingCapacity'), $dbRow['CarryingCapacity']);
        $this->checkStringColumn('GrossWeight', $this->getColumn($exportRow, 'GrossWeight'), $dbRow['GrossWeight']);
        $this->checkStringColumn('SurveyType', $this->getColumn($exportRow, 'SurveyType'), $dbRow['IsNorthernIreland'] === 1 ? '2' : '1');

        $this->columnWatcher->columnSeen('FuelTypeIsHybrid');
        $fuelType = $this->getColumn($exportRow, 'FuelType');
        if ($fuelType !== null) {
            $fuelType = str_replace('_', ' ', $fuelType);

            if ($this->getColumn($exportRow, 'FuelTypeIsHybrid') === '1') {
                $fuelType .= '-hybrid';
            }
        }

        $this->checkStringColumn('FuelType', $fuelType, $dbRow['FuelType']);

        $this->checkStringColumn('FuelQuantity', $this->getColumn($exportRow, 'FuelQuantity'), $dbRow['FuelQuantity']);
        $this->checkStringColumn('FuelQuantityOther', $this->getColumn($exportRow, 'FuelQuantityOther'), $dbRow['FuelQuantityOther']);
        $this->checkStringColumn('BusinessType', $this->getColumn($exportRow, 'BusinessType'), $dbRow['BusinessType'], true);
        $this->checkStringColumn('SurveyPostcode', $this->getColumn($exportRow, 'SurveyPostcode'), $dbRow['SurveyPostcode']);
        $this->checkStringColumn('EmployeeCount', $this->getColumn($exportRow, 'EmployeeCount'), $dbRow['EmployeeCount']);
        $this->checkStringColumn('RegisteredKeeper', $this->getColumn($exportRow, 'RegisteredKeeper'), $dbRow['RegisteredKeeper'], true);
        $this->checkStringColumn('PossessionState', $this->getColumn($exportRow, 'PossessionState'), $dbRow['PossessionState']);
        $this->checkStringColumn('AxleConfigurationID', $this->getColumn($exportRow, 'AxleConfigurationID'), $dbRow['AxleConfigurationCode']);

        // The export was slightly buggy with regards to NotUsed:
        // - It intended to export (stop_count + summary_count) == 0
        // - It did this by exporting survey.response.hasJourneys() which is a bit of PHP logic to calculate this
        // - However, surveys that haven't been filled (e.g. "invited", or "approved" before filling), don't
        //   have a survey.response.
        // - This raises an exception in the BooleanProperty code, which then instead exports the default,
        //   which is false.

        // So here we're simulating that lack of a response by checking PossessionState, so that we can
        // compare against the export.
        $isFilled = $dbRow['PossessionState'] !== null;
        $dbNotUsed = ($isFilled && ($dbRow['stop_count'] + $dbRow['summary_count']) === 0) ? '1' : '0';
        $this->checkStringColumn('NotUsed', $this->getColumn($exportRow, 'NotUsed'), $dbNotUsed);

        // We've converted from ArticOrRigidID to exporting just the raw TrailerConfigurationCode

        // Also it looks like this suffered from the same problem in the exports as NotUsed above
        // (If no survey.response, then it was exported as '0' instead of null)
        $articOrRigid = $isFilled && intval($dbRow['TrailerConfigurationCode']) === Vehicle::TRAILER_CONFIGURATION_ARTICULATED;
        $articOrRigid = $articOrRigid ? '1' : '0';

        $this->checkStringColumn('ArticOrRigidID', $this->getColumn($exportRow, 'ArticOrRigidID'), $articOrRigid);


        $dbReasonForEmptySurvey = SurveyResponse::EMPTY_SURVEY_REASON_EXPORT_IDS[$dbRow['ReasonForEmptySurvey']] ?? null;
        $this->checkStringColumn('NotUsedReasonID', $this->getColumn($exportRow, 'NotUsedReasonID'), $dbReasonForEmptySurvey === null ? null : (string) $dbReasonForEmptySurvey);

// Really this is purely testing the DB/view output.
//
// It turns out that people are managing to choose Reason != 'other' AND still filling
// ReasonOther, as the form does not set ReasonOther to NULL in such cases (i.e. no data mapper)
//
//        if ($dbRow['ReasonForEmptySurvey'] !== SurveyResponse::REASON_OTHER) {
//            if ($dbRow['ReasonForEmptySurveyOther'] !== null) {
//                $this->errorReporter->error('Expected ReasonForEmptySurveyOther to be NULL');
//            }
//        } else {
//            if ($dbRow['ReasonForEmptySurveyOther'] === null) {
//                $this->errorReporter->error('Expected ReasonForEmptySurveyOther to be non-NULL');
//            }
//        }

        $this->checkDateColumn('ScrappedStolenDate', $this->getColumn($exportRow, 'ScrappedStolenDate'), $dbRow['ScrappedStolenDate']);
        $this->checkDateColumn('SurveyStartDate', $this->getColumn($exportRow, 'SurveyStartDate'), $dbRow['SurveyPeriodStart']);
        $this->checkStringColumn('SurveyID', $this->getColumn($exportRow, 'SurveyID'), $dbRow['WeekNumber']);
    }
}