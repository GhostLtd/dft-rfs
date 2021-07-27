<?php

namespace App\Serializer\Normalizer\Domestic\Mapper;

use App\Serializer\Normalizer\Mapper\Mapper;
use Symfony\Component\PropertyAccess\PropertyAccess;

class SurveyTypeProperty implements Mapper
{
    protected string $propertyPath;

    public function __construct(string $propertyPath)
    {
        $this->propertyPath = $propertyPath;
    }

    public function getData($sourceData)
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        return (true === $accessor->getValue($sourceData, $this->propertyPath)) ? 2 : 1;
    }
}