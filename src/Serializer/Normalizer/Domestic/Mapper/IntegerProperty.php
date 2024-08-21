<?php

namespace App\Serializer\Normalizer\Domestic\Mapper;

use App\Serializer\Normalizer\Mapper\Mapper;
use Symfony\Component\PropertyAccess\PropertyAccess;

class IntegerProperty implements Mapper
{
    public function __construct(protected string $propertyPath, private bool $defaultValue = false)
    {
    }

    #[\Override]
    public function getData($sourceData)
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        try {
            $value = $accessor->getValue($sourceData, $this->propertyPath);
            return intval(filter_var($value,FILTER_SANITIZE_NUMBER_INT));
        } catch (\Throwable) {
            return $this->defaultValue;
        }
    }
}