<?php


namespace App\Serializer\Normalizer\Domestic;


use App\Entity\Distance;
use App\Entity\Domestic\Day;
use App\Entity\Domestic\DaySummary;
use App\Serializer\Normalizer\AbstractExportNormalizer;
use App\Serializer\Normalizer\Mapper\DistanceProperty;
use App\Serializer\Normalizer\Mapper\Property;
use App\Serializer\Normalizer\Domestic\Mapper\BooleanProperty;

class SummaryDayNormalizer extends AbstractExportNormalizer
{
    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return $data instanceof DaySummary;
    }

    protected function getMapping(): array
    {
        return [
            'QDJournerysFiveorMoreID' => new Property('id'),
            'QDSurveyDetailsID' => new Property('day.response.survey.id'),
//            'RegMark' => '', // all nulls in example
            'Origin' => new Property('originLocation'),
            'Destination' => new Property('destinationLocation'),
            'TypeOfGoods' => new Property('goodsDescriptionNormalized'),
            'Loaded' => new DistanceProperty('distanceTravelledLoaded', Distance::UNIT_KILOMETERS),
            'Empty' => new DistanceProperty('distanceTravelledUnloaded.value', Distance::UNIT_KILOMETERS),
            'WeightOfGoodsDelivered' => new Property('goodsUnloaded'),
            'WeightOfGoodsCollected' => new Property('goodsLoaded'),
            'NumberOfStopsForDelivery' => new Property('numberOfStopsUnloading'),
            'NumberOfStopsForCollection' => new Property('numberOfStopsLoading'),
            'NumberOfStopsForBoth' => new Property('numberOfStopsLoadingAndUnloading'),
//            'DateInput' => '', // All nulls in example data
//            'RowNumber' => '', // All nulls in example data
            'FurthestStop' => new Property('furthestStop'),
            'MOA' => new Property('cargoTypeCode'),
            'WhereBorderCrossed' => new Property('borderCrossingLocation'),
            'DayOfWeek' => new Property('day.number'),
//            'MOACode' => '', // All nulls in example data
            'FromPort' => new BooleanProperty('goodsTransferredFrom', Day::TRANSFERRED_PORT),
            'FromAir' => new BooleanProperty('goodsTransferredFrom', Day::TRANSFERRED_AIR),
            'FromRail' => new BooleanProperty('goodsTransferredFrom', Day::TRANSFERRED_RAIL),
            'ToPort' => new BooleanProperty('goodsTransferredTo', Day::TRANSFERRED_PORT),
            'ToAir' => new BooleanProperty('goodsTransferredTo', Day::TRANSFERRED_AIR),
            'ToRail' => new BooleanProperty('goodsTransferredTo', Day::TRANSFERRED_RAIL),
            'DangerousHazardousGoodsID' => new Property('hazardousGoodsCode'),
        ];
    }
}