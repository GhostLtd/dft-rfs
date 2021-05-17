<?php

namespace App\Serializer\Normalizer\Mapper;

use App\Serializer\Normalizer\International\Mapper\BooleanLiteral;
use Symfony\Component\PropertyAccess\PropertyAccess;

class BooleanProperty implements Mapper
{
    protected string $propertyPath;
    protected $trueIfEquals;

    public function __construct(string $propertyPath, $trueIfEquals = true)
    {
        $this->propertyPath = $propertyPath;
        $this->trueIfEquals = $trueIfEquals;
    }

    public function getData($sourceData)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        $value = $accessor->getValue($sourceData, $this->propertyPath);

        return ($value === $this->trueIfEquals) ? BooleanLiteral::TRUE : BooleanLiteral::FALSE;
    }
}