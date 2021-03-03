<?php


namespace App\Serializer\Normalizer\Domestic;


use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyResponse;
use App\Entity\Vehicle;
use App\Entity\Vehicle as BaseVehicle;
use App\Entity\Volume;
use App\Serializer\Normalizer\AbstractExportNormalizer;
use App\Serializer\Normalizer\Domestic\Mapper\BooleanEquivalencyProperty;
use App\Serializer\Normalizer\Domestic\Mapper\BooleanLiteral;
use App\Serializer\Normalizer\Domestic\Mapper\EpochWeekNumberProperty;
use App\Serializer\Normalizer\Domestic\Mapper\IntegerProperty;
use App\Serializer\Normalizer\Domestic\Mapper\ReturnCodeIdProperty;
use App\Serializer\Normalizer\Domestic\Mapper\SurveyTypeProperty;
use App\Serializer\Normalizer\Mapper\Property;
use App\Serializer\Normalizer\Domestic\Mapper\BooleanProperty;

class SurveyNormalizer extends AbstractExportNormalizer
{
    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return $data instanceof Survey;
    }

    protected function getMapping(): array
    {
        $true = new BooleanLiteral(true);
        $false = new BooleanLiteral(false);

        return [
            'QDSurveyDetailsID' => new Property('id'),
            'RegMark' => new Property('registrationMark'),
            'CarryingCapacity' => new Property('response.vehicle.carryingCapacity'),
            'CarryingCapacityNotEntered' => new BooleanEquivalencyProperty('response.vehicle.carryingCapacity', false, true),
            'OnOwnAccount' => new BooleanProperty('response.vehicle.operationType', BaseVehicle::OPERATION_TYPE_ON_OWN_ACCOUNT),
            'ForHireReward' => new BooleanProperty('response.vehicle.operationType', BaseVehicle::OPERATION_TYPE_FOR_HIRE_AND_REWARD),
            'MainlyOperatedNotEntered' => $false,
            'KMsLoadedYN' => $true,
            'MilesLoadedYN' => $false,
            'KMsEmptyYN' => $true,
            'MilesEmptyYN' => $false,
            'DateInput' => new Property('responseStartDate'), // team logged in to system (form returned)
            'DateCompleted' => new Property('submissionDate'), // team did data input (MS access)
            'FlatDropSided' => new BooleanProperty('response.vehicle.bodyType', BaseVehicle::BODY_TYPE_FLAT_DROP),
            'BoxNonSpecialised' => new BooleanProperty('response.vehicle.bodyType', BaseVehicle::BODY_TYPE_BOX),
            'TemperatureControlled' => new BooleanProperty('response.vehicle.bodyType', BaseVehicle::BODY_TYPE_TEMPERATURE_CONTROLLED),
            'CurtainSided' => new BooleanProperty('response.vehicle.bodyType', BaseVehicle::BODY_TYPE_CURTAIN_SIDED),
            'LiquidTanker' => new BooleanProperty('response.vehicle.bodyType', BaseVehicle::BODY_TYPE_LIQUID),
            'SolidBulkTanker' => new BooleanProperty('response.vehicle.bodyType', BaseVehicle::BODY_TYPE_SOLID_BULK),
            'LiveStockCarrier' => new BooleanProperty('response.vehicle.bodyType', BaseVehicle::BODY_TYPE_LIVESTOCK),
            'CarTansporter' => new BooleanProperty('response.vehicle.bodyType', BaseVehicle::BODY_TYPE_CAR),
            'Tipper' => new BooleanProperty('response.vehicle.bodyType', BaseVehicle::BODY_TYPE_TIPPER),
            'Other' => new BooleanProperty('response.vehicle.bodyType', BaseVehicle::BODY_TYPE_OTHER),
            'TrailerTypeNotEntered' => new BooleanEquivalencyProperty('response.vehicle.bodyType', false, true),
            'KMsLoadedYN5Plus' => $true,
            'MilesLoadedYN5Plus' => $false,
            'KMsEmptyYN5Plus' => $true,
            'MilesEmptyYN5Plus' => $false,
//            'QuestionaireType' => '', // 1 post? 0 email? (not needed)
//            'LastAddressLineDesc' => '', // literal '' ?
            'SurveyType' => new SurveyTypeProperty('isNorthernIreland'),
            'TripDataLoaded' => $false, // don't know, needed, but all false
            'SurveyStartDate' => new Property('surveyPeriodStart'),
//            'RecordLastUpdatedBy' => '', // approved by (email)
            'LitresOfFuelPurchased' => new Property('response.vehicle.fuelQuantity.value'),
            'Gallons' => new BooleanProperty('response.vehicle.fuelQuantity.unit', Volume::UNIT_GALLONS),
            'Litres' => new BooleanProperty('response.vehicle.fuelQuantity.unit', Volume::UNIT_LITRES),
//            'Comments' => '', // not use in export table
//            'CaryingCapacityAdjusted' => '', // will all be null, not required - lucy doesn't know
            'BusinessType' => new Property('response.businessNature'),
            'SurveyID' => new EpochWeekNumberProperty('surveyPeriodStart'),
//            'VehicleOwnerID' => '', // not needed
            'DateSurveySent' => new Property('notifiedDate'),
//            'DateSurveyReturned' => '',
//            'DateReminder1' => '', // not currently sending, handy to have
//            'DateReminder2' => '', // not currently sending, handy to have
//            'SurveyDiscardedReasonID' => '', // from lookup table
            'SurveyAddressLine1' => new Property('survey.invitationAddress.line2'),
            'SurveyAddressLine2' => new Property('survey.invitationAddress.line3'),
            'SurveyAddressLine3' => new Property('survey.invitationAddress.line4'),
            'SurveyAddressLine4' => new Property('survey.invitationAddress.line5'),
            'SurveyAddressLine5' => new Property('survey.invitationAddress.line6'),
            'SurveyPostcode' => new Property('survey.invitationAddress.postcode'),
//            'SurveyEmail' => '', // invitation email - not needed for our export
            'ContactTelNo' => new IntegerProperty('response.contactTelephone'),
// DVLA import data --->
//            'FuelTypePropulsionCode' => '',
//            'WheelPlanCode' => '',
//            'TaxationClass' => '',
//            'BodyTypeCodeID' => '',
//            'YearOfRegistration' => '',
//            'ArticRigid' => '',
//            'UnladenWeight' => '',
//            'GrossTrainWeight' => '',
//            'BodyTypeDescription' => '',
// <--- DVLA import data
            'NotUsedReasonID' => new Property('survey.response.reasonForEmptySurveyExportId'),
//            'LastUpdatedDate' => '', // date approved
            'RegisteredKeeper' => new Property('survey.invitationAddress.line1'),
//            'DateHireCompanyRequestSent' => '', // ??
//            'DateHireCompanyReminderSent' => '', // ??
//            'DateSurveyDueBack' => '', // ??
            'WithTrips' => new BooleanProperty('response.hasJourneys'),
            'Sold' => new BooleanProperty('response.isInPossessionOfVehicle', SurveyResponse::IN_POSSESSION_SOLD),
            'ScrappedStolen' => new BooleanProperty('response.isInPossessionOfVehicle', SurveyResponse::IN_POSSESSION_SCRAPPED_OR_STOLEN),
            'HireCompany' => new Property('response.hireeeName', ''),
            'ScrappedStolenDate' => new Property('response.unableToCompleteDate'),
            'Discard' => $false,
            'NotUsed' => new BooleanProperty('response.hasJourneys', false),
            'DisableReminders' => $false,
            'TickBoxForSortingRecords' => $false,
            'ContactName' => new Property('response.contactName'),
            'SendEMail' => $false,
            'ReturnCodeID' => new ReturnCodeIdProperty(),
            'ArticOrRigidID' => new BooleanProperty('response.vehicle.trailerConfiguration', Vehicle::TRAILER_CONFIGURATION_ARTICULATED),
            'AxleConfigurationID' => new Property('response.vehicle.axleConfiguration'),

        ];
    }
}