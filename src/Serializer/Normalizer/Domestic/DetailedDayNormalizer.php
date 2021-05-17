<?php


namespace App\Serializer\Normalizer\Domestic;


use App\Entity\AbstractGoodsDescription;
use App\Entity\Distance;
use App\Entity\Domestic\Day;
use App\Entity\Domestic\DayStop;
use App\Serializer\Normalizer\AbstractExportNormalizer;
use App\Serializer\Normalizer\Domestic\Mapper\BooleanProperty;
use App\Serializer\Normalizer\Domestic\Mapper\DayOfWeekProperty;
use App\Serializer\Normalizer\Mapper\Callback;
use App\Serializer\Normalizer\Mapper\NullIsZeroProperty;
use App\Serializer\Normalizer\Mapper\Property;
use App\Serializer\Normalizer\Mapper\ZeroIsNullProperty;

class DetailedDayNormalizer extends AbstractExportNormalizer
{
    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return $data instanceof DayStop;
    }

    protected function getMapping(): array
    {
        return [
            'QDJourneyDetailsFourOrLessID' => new Property('id'),
            'QDSurveyDetailsID' => new Property('day.response.survey.id'),
//            'RegMark' => '' // null in example,
            'Origin' => new Property('originLocation'),
            'Destination' => new Property('destinationLocation'),
            'TypeOfGoods' => new Property('goodsDescriptionNormalized'),
            'Loaded' => new Callback(function(DayStop $stop){
                if ($stop->getGoodsDescription() === AbstractGoodsDescription::GOODS_DESCRIPTION_EMPTY) {
                    return 0;
                }
                return $stop->getDistanceTravelled()->getValueNormalized(Distance::UNIT_KILOMETERS);
            }),
            'Empty' => new Callback(function(DayStop $stop){
                if ($stop->getGoodsDescription() !== AbstractGoodsDescription::GOODS_DESCRIPTION_EMPTY) {
                    return 0;
                }
                return $stop->getDistanceTravelled()->getValueNormalized(Distance::UNIT_KILOMETERS);
            }),
            'WeightOfGoodsCarriedKG' => new NullIsZeroProperty('weightOfGoodsCarried'),
            'RowNumber' => new Property('number'),
//            'DateInput' => '' // null in examples,
            'MOA' => new Property('cargoTypeCode'),
            'WhereBorderCrossed' => new Property('borderCrossingLocation'),
            'DayOfWeek' => new DayOfWeekProperty(),
//            'MOACode' => '', // null in examples
            '90PercentLtd' => new BooleanProperty('wasLimitedBySpace'),
            'LtdByWeight' => new BooleanProperty('wasLimitedByWeight'),
            'DangerousHazardousGoodsID' => new ZeroIsNullProperty('hazardousGoodsCode'),
            'FromPort' => new BooleanProperty('goodsTransferredFrom', Day::TRANSFERRED_PORT),
            'FromAir' => new BooleanProperty('goodsTransferredFrom', Day::TRANSFERRED_AIR),
            'FromRail' => new BooleanProperty('goodsTransferredFrom', Day::TRANSFERRED_RAIL),
            'ToPort' => new BooleanProperty('goodsTransferredTo', Day::TRANSFERRED_PORT),
            'ToAir' => new BooleanProperty('goodsTransferredTo', Day::TRANSFERRED_AIR),
            'ToRail' => new BooleanProperty('goodsTransferredTo', Day::TRANSFERRED_RAIL),
        ];
    }
}