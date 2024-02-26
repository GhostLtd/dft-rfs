<?php

namespace App\Serializer\Normalizer\Domestic;

use App\Entity\Distance;
use App\Entity\Domestic\Day;
use App\Entity\Domestic\DaySummary;
use App\Serializer\Normalizer\AbstractExportNormalizer;
use App\Serializer\Normalizer\Domestic\Mapper\DayOfWeekProperty;
use App\Serializer\Normalizer\Domestic\Mapper\MOAProperty;
use App\Serializer\Normalizer\Mapper\DistanceProperty;
use App\Serializer\Normalizer\Mapper\NullIsZeroProperty;
use App\Serializer\Normalizer\Mapper\Property;
use App\Serializer\Normalizer\Domestic\Mapper\BooleanProperty;
use App\Serializer\Normalizer\Mapper\ZeroIsNullProperty;

class SummaryDayNormalizer extends AbstractExportNormalizer
{
    #[\Override]
    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        return $data instanceof DaySummary;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            DaySummary::class => true,
        ];
    }
    #[\Override]
    protected function getMapping(): array
    {
        return [
            'QDJournerysFiveorMoreID' => new Property('id'),
            'QDSurveyDetailsID' => new Property('day.response.survey.id'),
            'RegMark' => new Property('day.response.survey.registrationMark'), // all nulls in example
            'Origin' => new Property('originLocation'),
            'Destination' => new Property('destinationLocation'),
            'TypeOfGoods' => new Property('goodsDescriptionNormalized'),
            'Loaded' => new DistanceProperty('distanceTravelledLoaded', Distance::UNIT_KILOMETRES),
            'Empty' => new DistanceProperty('distanceTravelledUnloaded', Distance::UNIT_KILOMETRES),
            'WeightOfGoodsDelivered' => new NullIsZeroProperty('weightOfGoodsUnloaded'),
            'WeightOfGoodsCollected' => new NullIsZeroProperty('weightOfGoodsLoaded'),
            'NumberOfStopsForDelivery' => new Property('numberOfStopsUnloading'),
            'NumberOfStopsForCollection' => new Property('numberOfStopsLoading'),
            'NumberOfStopsForBoth' => new Property('numberOfStopsLoadingAndUnloading'),
//            'DateInput' => '', // All nulls in example data
//            'RowNumber' => '', // All nulls in example data
            'FurthestStop' => new Property('furthestStop'),
            'MOA' => new MOAProperty(),
            'WhereBorderCrossed' => new Property('borderCrossingLocation'),
            'DayOfWeek' => new DayOfWeekProperty(),
//            'MOACode' => '', // All nulls in example data
            'FromPort' => new BooleanProperty('goodsTransferredFrom', Day::TRANSFERRED_PORT),
            'FromAir' => new BooleanProperty('goodsTransferredFrom', Day::TRANSFERRED_AIR),
            'FromRail' => new BooleanProperty('goodsTransferredFrom', Day::TRANSFERRED_RAIL),
            'ToPort' => new BooleanProperty('goodsTransferredTo', Day::TRANSFERRED_PORT),
            'ToAir' => new BooleanProperty('goodsTransferredTo', Day::TRANSFERRED_AIR),
            'ToRail' => new BooleanProperty('goodsTransferredTo', Day::TRANSFERRED_RAIL),
            'DangerousHazardousGoodsID' => new ZeroIsNullProperty('hazardousGoodsCode'),
        ];
    }
}
