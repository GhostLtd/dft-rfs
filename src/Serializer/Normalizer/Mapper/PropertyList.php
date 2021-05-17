<?php

namespace App\Serializer\Normalizer\Mapper;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Throwable;

class PropertyList implements Mapper
{
    protected array $propertyPathList;
    private $defaultValue;

    public function __construct(array $propertyPathList, $defaultValue = null)
    {
        $this->propertyPathList = $propertyPathList;
        $this->defaultValue = $defaultValue;
    }

    public function getData($sourceData)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        foreach ($this->propertyPathList as $propertyPath) {
            try {
                $value = $accessor->getValue($sourceData, $propertyPath);

                if ($value !== null) {
                    return $value;
                }
            }
            catch (Throwable $e) {}
        }

        return $this->defaultValue;
    }
}