<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class TripRoute extends Constraint
{
    public string $message = "common.trip-route.not-blank";
    public string $formField = "ports";
    public string $direction;

    public function __construct(
        string $message = null,
        string $formField = null,
        string $direction = null,
        array  $groups = [],
        mixed  $payload = null,
    )
    {
        parent::__construct([], $groups, $payload);

        $this->message = $message ?? $this->message;
        $this->formField = $formField ?? $this->formField;
        $this->direction = $direction ?? $this->direction;
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
