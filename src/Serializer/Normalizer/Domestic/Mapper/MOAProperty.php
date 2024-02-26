<?php

namespace App\Serializer\Normalizer\Domestic\Mapper;

use App\Entity\AbstractGoodsDescription;
use App\Serializer\Normalizer\Mapper\Mapper;
use Symfony\Component\PropertyAccess\PropertyAccess;

class MOAProperty implements Mapper
{
    public function __construct(protected string $cargoTypeCodePropertyPath = 'cargoTypeCode', protected string $goodsDescriptionPath = 'goodsDescription')
    {
    }

    #[\Override]
    public function getData($sourceData)
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        $cargoTypeCode = $accessor->getValue($sourceData, $this->cargoTypeCodePropertyPath);
        $goodsDescription = $accessor->getValue($sourceData, $this->goodsDescriptionPath);

        if ($cargoTypeCode === null && $goodsDescription === AbstractGoodsDescription::GOODS_DESCRIPTION_EMPTY) {
            $cargoTypeCode = 'NS';
        }

        return $cargoTypeCode;
    }
}