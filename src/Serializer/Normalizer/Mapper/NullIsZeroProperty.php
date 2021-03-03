<?php

namespace App\Serializer\Normalizer\Mapper;

use Symfony\Component\PropertyAccess\PropertyAccess;

class NullIsZeroProperty implements Mapper
{
    protected string $propertyPath;
    private $defaultValue;

    public function __construct(string $propertyPath, $defaultValue = 0)
    {
        $this->propertyPath = $propertyPath;
        $this->defaultValue = $defaultValue;
    }

    public function getData($sourceData)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        try {
            $value = $accessor->getValue($sourceData, $this->propertyPath);
            if ($value === null) {
                return 0;
            }
            return $value;
        } catch (\Throwable $e) {
            return $this->defaultValue;
        }
    }
}