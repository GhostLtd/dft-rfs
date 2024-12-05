<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraints\LessThanOrEqual;

class LessThanOrEqualDateValidator extends AbstractDateComparisonValidator
{
    #[\Override]
    protected function compareValues($value1, $value2): bool
    {
        return null === $value2 || $value1 <= $value2;
    }

    #[\Override]
    protected function getErrorCode(): ?string
    {
        return LessThanOrEqual::TOO_HIGH_ERROR;
    }
}
