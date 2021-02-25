<?php

namespace App\Serializer\Normalizer\Mapper;

class DomBooleanLiteral extends Literal
{
    const TRUE = 1;
    const FALSE = 0;

    public function __construct(bool $state)
    {
        parent::__construct($state ? self::TRUE : self::FALSE);
    }
}