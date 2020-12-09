<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraints\GreaterThan;

class GreaterThanDateValidator extends AbstractDateComparisonValidator
{
    protected function compareValues($value1, $value2)
    {
        return null === $value2 || $value1 > $value2;
    }

    protected function getErrorCode()
    {
        return GreaterThan::TOO_LOW_ERROR;
    }
}