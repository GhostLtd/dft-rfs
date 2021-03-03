<?php

namespace App\Serializer\Normalizer\Mapper;

use Symfony\Component\PropertyAccess\PropertyAccess;

class ZeroIsNullProperty implements Mapper
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
            $value = $accessor->getValue($sourceData, $this->propertyPath);
            if ($value === 0 || $value === "0") {
                return null;
            }
            return $value;
        } catch (\Throwable $e) {
            return $this->defaultValue;
        }
    }
}