<?php

namespace App\Serializer\Normalizer\International;

use App\Entity\Distance;
use App\Entity\International\Trip;
use App\Entity\Vehicle as BaseVehicle;
use App\Form\CountryType;
use App\Serializer\Normalizer\AbstractExportNormalizer;
use App\Serializer\Normalizer\International\Mapper\BooleanLiteral;
use App\Serializer\Normalizer\Mapper\BooleanPropertyList;
use App\Serializer\Normalizer\Mapper\Callback;
use App\Serializer\Normalizer\Mapper\Mapper;
use App\Serializer\Normalizer\Mapper\Literal;
use App\Serializer\Normalizer\Mapper\Property;
use App\Serializer\Normalizer\Mapper\BooleanProperty;
use App\Serializer\Normalizer\Mapper\PropertyList;
use Symfony\Component\Intl\Countries;

class TripNormalizer extends AbstractExportNormalizer
{
    private array $countryNames;

    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return $data instanceof Trip;
    }

    /**
     * @return Mapper[]|array
     */
    protected function getMapping(): array
    {
        $this->countryNames = Countries::getNames();
        return [
            'IHRVehicleDetailsID' => new Property('id'),
            'RegMark' => new Property('vehicle.registration_mark'),
            'RefNumber' => new Property('vehicle.survey_response.survey.reference_number'),
            'FirstDate' => new Property('outboundDate'),
            'SecondDate' => new Property('returnDate'),
            'GrossVehicleWeight' => new Property('vehicle.gross_weight'),
            'GrossVehicleWeightNotEntered' => new BooleanLiteral(false),
            'CarryingCapacity' => new Property('vehicle.carrying_capacity'),
            'CarryingCapacityNotEntered' => new BooleanLiteral(false),
            'OnOwnAccount' => new BooleanProperty('vehicle.operation_type', BaseVehicle::OPERATION_TYPE_ON_OWN_ACCOUNT),
            'ForHireReward' => new BooleanProperty('vehicle.operation_type', BaseVehicle::OPERATION_TYPE_FOR_HIRE_AND_REWARD),
            'MainlyOperatedNotEntered' => new BooleanLiteral(false),
            // DateInput                        - ??
            // DateCompleted                    - ??
            'FlatDropSided' => new BooleanPropertyList(['body_type', 'vehicle.body_type'], BaseVehicle::BODY_TYPE_FLAT_DROP),
            'BoxNonSpecialised' => new BooleanPropertyList(['body_type', 'vehicle.body_type'], BaseVehicle::BODY_TYPE_BOX),
            'TemperatureControlled' => new BooleanPropertyList(['body_type', 'vehicle.body_type'], BaseVehicle::BODY_TYPE_TEMPERATURE_CONTROLLED),
            'CurtainSided' => new BooleanPropertyList(['body_type', 'vehicle.body_type'], BaseVehicle::BODY_TYPE_CURTAIN_SIDED),
            'LiquidTanker' => new BooleanPropertyList(['body_type', 'vehicle.body_type'], BaseVehicle::BODY_TYPE_LIQUID),
            'SolidBulkTanker' => new BooleanPropertyList(['body_type', 'vehicle.body_type'], BaseVehicle::BODY_TYPE_SOLID_BULK),
            'LiveStockCarrier' => new BooleanPropertyList(['body_type', 'vehicle.body_type'], BaseVehicle::BODY_TYPE_LIVESTOCK),
            'CarTansporter' => new BooleanPropertyList(['body_type', 'vehicle.body_type'], BaseVehicle::BODY_TYPE_CAR), // N.B. Typo as per target
            'Tipper' => new BooleanPropertyList(['body_type', 'vehicle.body_type'], BaseVehicle::BODY_TYPE_TIPPER),
            'Other' => new BooleanPropertyList(['body_type', 'vehicle.body_type'], BaseVehicle::BODY_TYPE_OTHER),
            'TrailerTypeNotEntered' => new BooleanLiteral(false),
            // QuestionaireType                 - ?? // N.B. Typo as per target
            // LastAddressLineDesc              - ??
            'SurveyType' => new Literal(3),
            // TripDataLoaded                   - ??
            'VehicleOrigin' => new Property('origin'),
            'VehicleDestination' => new Property('destination'),
            'UKPortOfDeparture' => new Property('outbound_uk_port'),
            'ForeignPortOfArrival' => new Property('outbound_foreign_port'),
            'OutwardVehicleEmpty' => new BooleanProperty('outbound_was_empty'),
            'UKPortOfArrival' => new Property('return_uk_port'),
            'ForeignPortOfDeparture' => new Property('return_foreign_port'),
            'ReturnVehicleEmpty' => new BooleanProperty('return_was_empty'),
            'CountriesTransitted' => new Callback(function(Trip $sourceData) {
                // The trip countries
                $countries = $sourceData->getCountriesTransitted();
                $countriesOther = $sourceData->getCountriesTransittedOther();
                if ($countriesOther !== null) {
                    foreach(array_map('trim', explode(',', $countriesOther)) as $country) {
                        $countries[] = $this->resolveOtherCountryToCode($country);
                    }
                }

                // Add countries from actions
                foreach ($sourceData->getActions() as $action) {
                    $country = $action->getCountry();
                    if ($country === CountryType::OTHER) {
                        $country = $this->resolveOtherCountryToCode(trim($action->getCountryOther()));
                    }
                    $countries[] =  $country;
                }

                return join(', ', array_unique($countries));
            }),
            'TotalRoundTripDistance' => new Property('round_trip_distance.value'),
            'KMsYN' => new BooleanProperty('round_trip_distance.unit', Distance::UNIT_KILOMETRES),
            'MilesYN' => new BooleanProperty('round_trip_distance.unit', Distance::UNIT_MILES),
            'DatesNotEntered' => new BooleanLiteral(false),
            'OutwardJourneyNotEntered' => new BooleanLiteral(false),
            'ReturnJouneyNotEntered' => new BooleanLiteral(false), // N.B. Typo as per target
            'TripInfoNotEntered' => new BooleanLiteral(false),
            // SurveyStartDate                  - ??
            // RecordLastUpdatedBy              - ??
            // AdjustedGrossVehicleWeight       - ??
            // AdjustedCarryingCapacity         - ??
            'BusinessType' => new Property('vehicle.survey_response.business_nature'),
            // Comments                         - ??
            'Outward90PercentLtd' => new BooleanProperty('outbound_was_limited_by_space'),
            'OutwardLtdByWeight' => new BooleanProperty('outbound_was_limited_by_weight'),
            'Return90PercentLtd' => new BooleanProperty('return_was_limited_by_space'),
            'ReturnLtdByWeight' => new BooleanProperty('return_was_limited_by_weight'),
            'AxleConfig' => new PropertyList(['axle_configuration', 'vehicle.axle_configuration']),
        ];
    }

    protected function resolveOtherCountryToCode($country)
    {
        $countryCode = array_search($country, $this->countryNames);
        return $countryCode !== false ? $countryCode : $country;
    }
}