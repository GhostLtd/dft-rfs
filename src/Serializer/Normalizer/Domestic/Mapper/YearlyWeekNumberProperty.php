<?php


namespace App\Serializer\Normalizer\Domestic\Mapper;


use App\Serializer\Normalizer\Mapper\Mapper;
use App\Utility\Domestic\WeekNumberHelper;
use Symfony\Component\PropertyAccess\PropertyAccess;

class YearlyWeekNumberProperty implements Mapper
{
    protected string $propertyPath;

    public function __construct(string $propertyPath)
    {
        $this->propertyPath = $propertyPath;
    }

    public function getData($sourceData)
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        [$weekNumber, $year] = WeekNumberHelper::getYearlyWeekNumberAndYear($accessor->getValue($sourceData, $this->propertyPath));
        return $weekNumber;
    }
}