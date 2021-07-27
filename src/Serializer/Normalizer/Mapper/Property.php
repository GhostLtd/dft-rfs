<?php

namespace App\Serializer\Normalizer\Mapper;

use Symfony\Component\PropertyAccess\PropertyAccess;

class Property implements Mapper
{
    protected string $propertyPath;
    private $defaultValue;

    public function __construct(string $propertyPath, $defaultValue = null)
    {
        $this->propertyPath = $propertyPath;
        $this->defaultValue = $defaultValue;
    }

    public function getData($sourceData)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        try {
            return $accessor->getValue($sourceData, $this->propertyPath);
        } catch (\Throwable $e) {
            return $this->defaultValue;
        }
    }
}