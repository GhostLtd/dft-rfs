<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class DriverAvailabilityWagesPeriod extends Constraint
{
    public string $message = 'Choose a period for this wage increase.';

    public function __construct(
        public string $triggerField,
        public string $targetField,
        string $message = null,
        array  $groups = [],
        mixed  $payload = null,
    )
    {
        parent::__construct([], $groups, $payload);

        $this->message = $message ?? $this->message;
    }

    #[\Override]
    public function validatedBy(): string
    {
        return static::class . 'Validator';
    }

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
