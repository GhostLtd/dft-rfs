<?php

namespace App\Serializer\Normalizer\Mapper;

use App\Serializer\Normalizer\International\Mapper\BooleanLiteral;

class BooleanPropertyList extends PropertyList
{
    protected $trueIfEquals;

    public function __construct(array $propertyPathList, $trueIfEquals = true)
    {
        parent::__construct($propertyPathList);
        $this->trueIfEquals = $trueIfEquals;
    }

    public function getData($sourceData)
    {
        $value = parent::getData($sourceData);
        return ($value === $this->trueIfEquals) ? BooleanLiteral::TRUE : BooleanLiteral::FALSE;
    }
}