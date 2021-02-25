<?php

namespace App\Utility\International;

use App\Entity\Distance;
use App\Entity\International\Trip;
use App\Entity\Vehicle as BaseVehicle;
use App\Serializer\Normalizer\AbstractExportNormalizer;
use App\Serializer\Normalizer\Mapper\IntBooleanLiteral;
use App\Serializer\Normalizer\Mapper\Mapper;
use App\Serializer\Normalizer\Mapper\Literal;
use App\Serializer\Normalizer\Mapper\Property;
use App\Serializer\Normalizer\Mapper\PropertyBool;

class TripNormalizer extends AbstractExportNormalizer
{
    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return $data instanceof Trip;
    }

    /**
     * @return Mapper[]|array
     */
    protected function getMapping(): array
    {
        return [
            'IRHVehicleDetailsID' => new Property('id'),
            'RegMark' => new Property('vehicle.registration_mark'),
            'RefNumber' => new Property('vehicle.survey_response.survey.reference_number'),
            // FirstDate                        - ??
            // SecondDate                       - ??
            'GrossVehicleWeight' => new Property('vehicle.gross_weight'),
            'GrossVehicleWeightNotEntered' => new IntBooleanLiteral(false),
            'CarryingCapacity' => new Property('vehicle.carrying_capacity'),
            'CarryingCapacityNotEntered' => new IntBooleanLiteral(false),
            'OnOwnAccount' => new PropertyBool('vehicle.operation_type', BaseVehicle::OPERATION_TYPE_ON_OWN_ACCOUNT),
            'ForHireReward' => new PropertyBool('vehicle.operation_type', BaseVehicle::OPERATION_TYPE_FOR_HIRE_AND_REWARD),
            'MainlyOperatedNotEntered' => new IntBooleanLiteral(false),
            // DateInput                        - ??
            // DateCompleted                    - ??
            'FlatDropSided' => new PropertyBool('vehicle.body_type', BaseVehicle::BODY_TYPE_FLAT_DROP),
            'BoxNonSpecialised' => new PropertyBool('vehicle.body_type', BaseVehicle::BODY_TYPE_BOX),
            'TemperatureControlled' => new PropertyBool('vehicle.body_type', BaseVehicle::BODY_TYPE_TEMPERATURE_CONTROLLED),
            'CurtainSided' => new PropertyBool('vehicle.body_type', BaseVehicle::BODY_TYPE_CURTAIN_SIDED),
            'LiquidTanker' => new PropertyBool('vehicle.body_type', BaseVehicle::BODY_TYPE_LIQUID),
            'SolidBulkTanker' => new PropertyBool('vehicle.body_type', BaseVehicle::BODY_TYPE_SOLID_BULK),
            'LiveStockCarrier' => new PropertyBool('vehicle.body_type', BaseVehicle::BODY_TYPE_LIVESTOCK),
            'CarTansporter' => new PropertyBool('vehicle.body_type', BaseVehicle::BODY_TYPE_CAR),
            'Tipper' => new PropertyBool('vehicle.body_type', BaseVehicle::BODY_TYPE_TIPPER),
            'Other' => new PropertyBool('vehicle.body_type', BaseVehicle::BODY_TYPE_OTHER),
            'TrailerTypeNotEntered' => new IntBooleanLiteral(false),
            // QuestionaireType                 - ??
            // LastAddressLineDesc              - ??
            'SurveyType' => new Literal(3),
            // TripDataLoaded                   - ??
            'UKPortOfDeparture' => new Property('outbound_uk_port'),
            'ForeignPortOfArrival' => new Property('outbound_foreign_port'),
            'OutwardVehicleEmpty' => new PropertyBool('outbound_was_empty'),
            'UKPortOfArrival' => new Property('return_uk_port'),
            'ForeignPortOfDeparture' => new Property('return_foreign_port'),
            'ReturnVehicleEmpty' => new PropertyBool('return_was_empty'),
            // CountriesTransitted              - ?? (+ Other)
            'TotalRoundTripDistance' => new Property('round_trip_distance.value'),
            'KMsYN' => new PropertyBool('round_trip_distance.unit', Distance::UNIT_KILOMETERS),
            'MilesYN' => new PropertyBool('round_trip_distance.unit', Distance::UNIT_MILES),
            'DatesNotEntered' => new IntBooleanLiteral(false),
            'OutwardJourneyNotEntered' => new IntBooleanLiteral(false),
            'ReturnJouneyNotEntered' => new IntBooleanLiteral(false), // N.B. Typo as per target
            'TripInfoNotEntered' => new IntBooleanLiteral(false),
            // SurveyStartDate                  - ??
            // RecordLastUpdatedBy              - ??
            // AdjustedGrossVehicleWeight       - ??
            // AdjustedCarryingCapacity         - ??
            'BusinessNature' => new Property('vehicle.survey_response.business_nature'),
        ];
    }
}