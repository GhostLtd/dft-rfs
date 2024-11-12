<?php

namespace App\Serializer\Normalizer\Domestic\Mapper;

use App\Serializer\Normalizer\Mapper\Mapper;
use Symfony\Component\PropertyAccess\PropertyAccess;

class BooleanProperty implements Mapper
{
    public function __construct(protected string $propertyPath, protected mixed $trueIfEquals = true, protected ?bool $defaultValue = false, protected bool $nullable = false)
    {
    }

    #[\Override]
    public function getData($sourceData)
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        try {
            $propertyValue = $accessor->getValue($sourceData, $this->propertyPath);
            if (is_null($propertyValue) && $this->nullable) {
                return null;
            }

            $value = ($this->trueIfEquals === $propertyValue);
        } catch (\Throwable) {
            $value = $this->defaultValue;
        }

        return $value ? BooleanLiteral::TRUE : BooleanLiteral::FALSE;
    }
}