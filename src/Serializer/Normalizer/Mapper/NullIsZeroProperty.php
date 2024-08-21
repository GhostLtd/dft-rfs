<?php

namespace App\Serializer\Normalizer\Mapper;

use Symfony\Component\PropertyAccess\PropertyAccess;

class NullIsZeroProperty implements Mapper
{
    public function __construct(protected string $propertyPath, private $defaultValue = 0)
    {
    }

    #[\Override]
    public function getData($sourceData)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        try {
            $value = $accessor->getValue($sourceData, $this->propertyPath);
            if ($value === null) {
                return 0;
            }
            return $value;
        } catch (\Throwable) {
            return $this->defaultValue;
        }
    }
}