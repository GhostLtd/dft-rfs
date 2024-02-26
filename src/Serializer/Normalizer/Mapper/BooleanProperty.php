<?php

namespace App\Serializer\Normalizer\Mapper;

use App\Serializer\Normalizer\International\Mapper\BooleanLiteral;
use Symfony\Component\PropertyAccess\PropertyAccess;

class BooleanProperty implements Mapper
{
    public function __construct(protected string $propertyPath, protected $trueIfEquals = true)
    {
    }

    #[\Override]
    public function getData($sourceData)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        $value = $accessor->getValue($sourceData, $this->propertyPath);

        return ($value === $this->trueIfEquals) ? BooleanLiteral::TRUE : BooleanLiteral::FALSE;
    }
}