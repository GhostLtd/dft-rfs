<?php

namespace App\Serializer\Normalizer\Domestic\Mapper;

use App\Entity\AbstractGoodsDescription;
use App\Serializer\Normalizer\Mapper\Mapper;
use Symfony\Component\PropertyAccess\PropertyAccess;

class MOAProperty implements Mapper
{
    protected string $cargoTypeCodePropertyPath;
    protected string $goodsDescriptionPath;

    public function __construct(string $cargoTypeCodePropertyPath = 'cargoTypeCode', string $goodsDescriptionPath = 'goodsDescription')
    {
        $this->cargoTypeCodePropertyPath = $cargoTypeCodePropertyPath;
        $this->goodsDescriptionPath = $goodsDescriptionPath;
    }

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