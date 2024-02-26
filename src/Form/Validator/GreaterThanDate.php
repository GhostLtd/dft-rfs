<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraints\GreaterThan;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class GreaterThanDate extends GreaterThan
{
    public function __construct(
        mixed  $value = null,
        string $propertyPath = null,
        string $message = 'common.date.greater-than',
        array  $groups = null,
        mixed  $payload = null,
        array  $options = []
    )
    {
        parent::__construct($value, $propertyPath, $message, $groups, $payload, $options);
    }
}
