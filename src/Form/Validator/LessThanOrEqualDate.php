<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraints\LessThanOrEqual;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class LessThanOrEqualDate extends LessThanOrEqual
{
    public function __construct(
        mixed $value = null,
        ?string $propertyPath = null,
        string $message = 'common.date.less-than-or-equal',
        ?array $groups = null,
        mixed $payload = null,
    )
    {
        parent::__construct($value, $propertyPath, $message, $groups, $payload);
    }
}
