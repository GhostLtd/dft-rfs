<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class GreaterThanOrEqualDateValidator extends AbstractDateComparisonValidator
{
    #[\Override]
    protected function compareValues($value1, $value2): bool
    {
        return null === $value2 || $value1 >= $value2;
    }

    #[\Override]
    protected function getErrorCode(): ?string
    {
        return GreaterThanOrEqual::TOO_LOW_ERROR;
    }
}
