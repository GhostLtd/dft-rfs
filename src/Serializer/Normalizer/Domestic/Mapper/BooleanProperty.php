<?php

namespace App\Serializer\Normalizer\Domestic\Mapper;

use App\Serializer\Normalizer\Mapper\Mapper;
use Symfony\Component\PropertyAccess\PropertyAccess;

class BooleanProperty implements Mapper
{
    protected string $propertyPath;
    protected $trueIfEquals;
    private bool $defaultValue;

    public function __construct(string $propertyPath, $trueIfEquals = true, $defaultValue = false)
    {
        $this->propertyPath = $propertyPath;
        $this->trueIfEquals = $trueIfEquals;
        $this->defaultValue = $defaultValue;
    }

    public function getData($sourceData)
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        try {
            $value = ($this->trueIfEquals === $accessor->getValue($sourceData, $this->propertyPath));
        } catch (\Throwable $e) {
            $value = $this->defaultValue;
        }

        return $value ? BooleanLiteral::TRUE : BooleanLiteral::FALSE;
    }
}