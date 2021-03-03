<?php

namespace App\Serializer\Normalizer\Domestic\Mapper;

use App\Serializer\Normalizer\Mapper\Mapper;
use Symfony\Component\PropertyAccess\PropertyAccess;

class IntegerProperty implements Mapper
{
    protected string $propertyPath;
    private bool $defaultValue;

    public function __construct(string $propertyPath, $defaultValue = false)
    {
        $this->propertyPath = $propertyPath;
        $this->defaultValue = $defaultValue;
    }

    public function getData($sourceData)
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        try {
            $value = $accessor->getValue($sourceData, $this->propertyPath);
            return intval(filter_var($value,FILTER_SANITIZE_NUMBER_INT));
        } catch (\Throwable $e) {
            return $this->defaultValue;
        }
    }
}