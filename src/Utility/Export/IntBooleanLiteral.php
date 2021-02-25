<?php

namespace App\Utility\Export;

class IntBooleanLiteral extends Literal
{
    const TRUE = -1;
    const FALSE = 0;

    public function __construct(bool $state)
    {
        parent::__construct($state ? self::TRUE : self::FALSE);
    }
}