<?php

namespace App\Serializer\Normalizer\Mapper;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Throwable;

class PropertyList implements Mapper
{
    public function __construct(protected array $propertyPathList, private $defaultValue = null)
    {
    }

    #[\Override]
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
            catch (Throwable) {}
        }

        return $this->defaultValue;
    }
}