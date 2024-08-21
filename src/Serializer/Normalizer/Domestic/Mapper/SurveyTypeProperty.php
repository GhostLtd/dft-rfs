<?php

namespace App\Serializer\Normalizer\Domestic\Mapper;

use App\Serializer\Normalizer\Mapper\Mapper;
use Symfony\Component\PropertyAccess\PropertyAccess;

class SurveyTypeProperty implements Mapper
{
    public function __construct(protected string $propertyPath)
    {
    }

    #[\Override]
    public function getData($sourceData)
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        return (true === $accessor->getValue($sourceData, $this->propertyPath)) ? 2 : 1;
    }
}