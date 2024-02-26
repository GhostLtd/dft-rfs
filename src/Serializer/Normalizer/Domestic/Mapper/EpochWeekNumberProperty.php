<?php


namespace App\Serializer\Normalizer\Domestic\Mapper;


use App\Serializer\Normalizer\Mapper\Mapper;
use DateTime;
use Symfony\Component\PropertyAccess\PropertyAccess;

class EpochWeekNumberProperty implements Mapper
{
    public function __construct(protected string $propertyPath)
    {
    }

    #[\Override]
    public function getData($sourceData)
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        // 2011-01-03 was week number 365...
        // however, 2016-01-04 was week number 693 (they seemed to skip 67 numbers from the previous week to this one)
        // and then a random gap of 1 mid 2018 (week 838 was 2018-10-08, but the week before was week 836)
        return 838 + floor((new DateTime('2018-10-08'))->diff($accessor->getValue($sourceData, $this->propertyPath))->days / 7);
    }
}