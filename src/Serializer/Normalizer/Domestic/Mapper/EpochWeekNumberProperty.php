<?php


namespace App\Serializer\Normalizer\Domestic\Mapper;


use App\Serializer\Normalizer\Mapper\Mapper;
use Symfony\Component\PropertyAccess\PropertyAccess;

class EpochWeekNumberProperty implements Mapper
{
    protected string $propertyPath;

    public function __construct(string $propertyPath)
    {
        $this->propertyPath = $propertyPath;
    }

    public function getData($sourceData)
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        return floor((new \DateTime('2003-01-01'))->diff($accessor->getValue($sourceData, $this->propertyPath))->days / 7);
    }
}