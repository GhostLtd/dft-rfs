<?php

namespace App\Serializer\Normalizer\International;

use App\Entity\International\Action;
use App\Serializer\Normalizer\AbstractExportNormalizer;
use App\Serializer\Normalizer\Mapper\CallbackProperty;
use App\Serializer\Normalizer\Mapper\Mapper;
use App\Serializer\Normalizer\Mapper\Property;

class ActionNormalizer extends AbstractExportNormalizer
{
    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return $data instanceof Action;
    }

    /**
     * @return Mapper[]|array
     */
    protected function getMapping(): array
    {
        return [
            'IRHConsignmentDetailsID' => new Property('id'),
            'IRHVehicleDetailsID' => new Property('trip.id'),
//            'MOA' => '', // ??
            'TypeOfGoods' => new Property('loadingAction.goodsDescription'),
            // 'DangerousGoods' => 8?
            'WeightOfGoodsCarriedKG' => new CallbackProperty(function(Action $sourceData) {
                return $sourceData->getWeightUnloadedAll() ?
                    $sourceData->getLoadingAction()->getWeightOfGoods() :
                    $sourceData->getWeightOfGoods();
            })
        ];
    }
}