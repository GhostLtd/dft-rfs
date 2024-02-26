<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class UniqueNotificationInterceptionName extends Constraint
{
    public string $message = "Company name '{{ value }}' has already been used";

    public function __construct(
        string $message = null,
        array $groups = [],
        mixed $payload = null,
    )
    {
        parent::__construct([], $groups, $payload);

        $this->message = $message ?? $this->message;
    }

    #[\Override]
    public function validatedBy(): string {
        return static::class.'Validator';
    }

    #[\Override]
    public function getTargets(): string {
        return self::CLASS_CONSTRAINT;
    }
}
