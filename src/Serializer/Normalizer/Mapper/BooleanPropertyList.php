<?php

namespace App\Serializer\Normalizer\Mapper;

use App\Serializer\Normalizer\International\Mapper\BooleanLiteral;

class BooleanPropertyList extends PropertyList
{
    public function __construct(array $propertyPathList, protected $trueIfEquals = true)
    {
        parent::__construct($propertyPathList);
    }

    #[\Override]
    public function getData($sourceData)
    {
        $value = parent::getData($sourceData);

        if (is_array($this->trueIfEquals)) {
            foreach($this->trueIfEquals as $trueIfEquals) {
                if ($value === $trueIfEquals) {
                    return BooleanLiteral::TRUE;
                }
            }
            return BooleanLiteral::FALSE;
        } else {
            return ($value === $this->trueIfEquals) ? BooleanLiteral::TRUE : BooleanLiteral::FALSE;
        }
    }
}