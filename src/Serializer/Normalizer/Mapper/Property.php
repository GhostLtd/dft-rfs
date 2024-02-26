<?php

namespace App\Serializer\Normalizer\Mapper;

use Symfony\Component\PropertyAccess\PropertyAccess;

class Property implements Mapper
{
    public function __construct(protected string $propertyPath, private $defaultValue = null)
    {
    }

    #[\Override]
    public function getData($sourceData)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        try {
            return $accessor->getValue($sourceData, $this->propertyPath);
        } catch (\Throwable) {
            return $this->defaultValue;
        }
    }
}