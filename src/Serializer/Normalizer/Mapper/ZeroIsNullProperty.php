<?php

namespace App\Serializer\Normalizer\Mapper;

use Symfony\Component\PropertyAccess\PropertyAccess;

class ZeroIsNullProperty implements Mapper
{
    public function __construct(protected string $propertyPath, private $defaultValue = null)
    {
    }

    #[\Override]
    public function getData($sourceData)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        try {
            $value = $accessor->getValue($sourceData, $this->propertyPath);
            if ($value === 0 || $value === "0") {
                return null;
            }
            return $value;
        } catch (\Throwable) {
            return $this->defaultValue;
        }
    }
}