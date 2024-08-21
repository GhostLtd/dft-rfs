<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class GreaterThanOrEqualDate extends GreaterThanOrEqual
{
    public function __construct(
        mixed $value = null,
        string $propertyPath = null,
        string $message = 'common.date.greater-than-or-equal',
        array $groups = null,
        mixed $payload = null,
        array $options = []
    )
    {
        parent::__construct($value, $propertyPath, $message, $groups, $payload, $options);
    }
}
