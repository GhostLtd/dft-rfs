<?php

namespace App\Serializer\Normalizer\Domestic\Mapper;

use App\Serializer\Normalizer\Mapper\Mapper;
use Symfony\Component\PropertyAccess\PropertyAccess;

class BooleanEquivalencyProperty implements Mapper
{
    public function __construct(protected string $propertyPath, protected $trueIfEquals = true, private bool $defaultValue = false)
    {
    }

    #[\Override]
    public function getData($sourceData)
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        try {
            $value = ($this->trueIfEquals == $accessor->getValue($sourceData, $this->propertyPath));
        } catch (\Throwable) {
            $value = $this->defaultValue;
        }

        return $value ? BooleanLiteral::TRUE : BooleanLiteral::FALSE;
    }
}