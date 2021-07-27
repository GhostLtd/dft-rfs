<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class GreaterThanOrEqualDateValidator extends AbstractDateComparisonValidator
{
    protected function compareValues($value1, $value2)
    {
        return null === $value2 || $value1 >= $value2;
    }

    protected function getErrorCode()
    {
        return GreaterThanOrEqual::TOO_LOW_ERROR;
    }
}