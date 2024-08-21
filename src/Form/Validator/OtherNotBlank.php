<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class OtherNotBlank extends Constraint
{
    public string $message = 'This value should not be blank.';
    public string $triggerValue = "other";

    public function __construct(
        public string $selectField,
        public ?string $otherField = null,
        string $message = null,
        string $triggerValue = null,
        array $groups = [],
        mixed $payload = null,
    )
    {
        parent::__construct([], $groups, $payload);
        $this->message = $message ?? $this->message;
        $this->triggerValue = $triggerValue ?? $this->triggerValue;
    }

    #[\Override]
    public function validatedBy() : string {
        return static::class.'Validator';
    }

    #[\Override]
    public function getTargets(): string|array {
        return self::CLASS_CONSTRAINT;
    }
}
