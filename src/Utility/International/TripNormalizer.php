<?php

namespace App\Utility\International;

use App\Entity\Distance;
use App\Entity\International\Trip;
use App\Entity\Vehicle as BaseVehicle;
use App\Utility\Export\BooleanLiteral;
use App\Utility\Export\Mapper;
use App\Utility\Export\Literal;
use App\Utility\Export\Property;
use App\Utility\Export\PropertyBool;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class TripNormalizer implements ContextAwareNormalizerInterface
{
    protected SerializerInterface $serializer;

    public function __construct()
    {
        $normalizer = new ObjectNormalizer(null, new CamelCaseToSnakeCaseNameConverter(), null, null, null, null, [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function($object, $format, $context) {
                return get_class($object).':'.$object->getId();
            },
        ]);

        $this->serializer = new Serializer([new DateTimeNormalizer(), $normalizer]);
    }

    public function normalize($object, $format = null, array $context = [])
    {
        $sourceData = $this->serializer->normalize($object, $format, $context);

        $mapping = [
            'IRHVehicleDetailsID' => new Property('[id]'),
            'RegMark' => new Property('[vehicle][registration_mark]'),
            'RefNumber' => new Property('[vehicle][survey_response][survey][reference_number]'),
            // FirstDate                        - ??
            // SecondDate                       - ??
            'GrossVehicleWeight' => new Property('[vehicle][gross_weight]'),
            'GrossVehicleWeightNotEntered' => new BooleanLiteral(false),
            'CarryingCapacity' => new Property('[vehicle][carrying_capacity]'),
            'CarryingCapacityNotEntered' => new BooleanLiteral(false),
            'OnOwnAccount' => new PropertyBool('[vehicle][operation_type]', BaseVehicle::OPERATION_TYPE_ON_OWN_ACCOUNT),
            'ForHireReward' => new PropertyBool('[vehicle][operation_type]', BaseVehicle::OPERATION_TYPE_FOR_HIRE_AND_REWARD),
            'MainlyOperatedNotEntered' => new BooleanLiteral(false),
            // DateInput                        - ??
            // DateCompleted                    - ??
            'FlatDropSided' => new PropertyBool('[vehicle][body_type]', BaseVehicle::BODY_TYPE_FLAT_DROP),
            'BoxNonSpecialised' => new PropertyBool('[vehicle][body_type]', BaseVehicle::BODY_TYPE_BOX),
            'TemperatureControlled' => new PropertyBool('[vehicle][body_type]', BaseVehicle::BODY_TYPE_TEMPERATURE_CONTROLLED),
            'CurtainSided' => new PropertyBool('[vehicle][body_type]', BaseVehicle::BODY_TYPE_CURTAIN_SIDED),
            'LiquidTanker' => new PropertyBool('[vehicle][body_type]', BaseVehicle::BODY_TYPE_LIQUID),
            'SolidBulkTanker' => new PropertyBool('[vehicle][body_type]', BaseVehicle::BODY_TYPE_SOLID_BULK),
            'LiveStockCarrier' => new PropertyBool('[vehicle][body_type]', BaseVehicle::BODY_TYPE_LIVESTOCK),
            'CarTansporter' => new PropertyBool('[vehicle][body_type]', BaseVehicle::BODY_TYPE_CAR),
            'Tipper' => new PropertyBool('[vehicle][body_type]', BaseVehicle::BODY_TYPE_TIPPER),
            'Other' => new PropertyBool('[vehicle][body_type]', BaseVehicle::BODY_TYPE_OTHER),
            'TrailerTypeNotEntered' => new BooleanLiteral(false),
            // QuestionaireType                 - ??
            // LastAddressLineDesc              - ??
            'SurveyType' => new Literal(3),
            // TripDataLoaded                   - ??
            'UKPortOfDeparture' => new Property('[outbound_uk_port]'),
            'ForeignPortOfArrival' => new Property('[outbound_foreign_port]'),
            'OutwardVehicleEmpty' => new PropertyBool('[outbound_was_empty]'),
            'UKPortOfArrival' => new Property('[return_uk_port]'),
            'ForeignPortOfDeparture' => new Property('[return_foreign_port]'),
            'ReturnVehicleEmpty' => new PropertyBool('[return_was_empty]'),
            // CountriesTransitted              - ?? (+ Other)
            'TotalRoundTripDistance' => new Property('[round_trip_distance][value]'),
            'KMsYN' => new PropertyBool('[round_trip_distance][unit]', Distance::UNIT_KILOMETERS),
            'MilesYN' => new PropertyBool('[round_trip_distance][unit]', Distance::UNIT_MILES),
            'DatesNotEntered' => new BooleanLiteral(false),
            'OutwardJourneyNotEntered' => new BooleanLiteral(false),
            'ReturnJouneyNotEntered' => new BooleanLiteral(false), // N.B. Typo as per target
            'TripInfoNotEntered' => new BooleanLiteral(false),
            // SurveyStartDate                  - ??
            // RecordLastUpdatedBy              - ??
            // AdjustedGrossVehicleWeight       - ??
            // AdjustedCarryingCapacity         - ??
            'BusinessNature' => new Property('[vehicle][survey_response][business_nature]'),
        ];

        return array_map(fn(Mapper $mapper) => $mapper->getData($sourceData), $mapping);
    }

    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return $data instanceof Trip;
    }
}