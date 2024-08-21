<?php

namespace App\Serializer\Normalizer\International\Mapper;

use App\Serializer\Normalizer\Mapper\Literal;

class BooleanLiteral extends Literal
{
    public const TRUE = 1;
    public const FALSE = 0;

    public function __construct(bool $state)
    {
        parent::__construct($state ? self::TRUE : self::FALSE);
    }
}