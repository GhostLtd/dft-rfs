<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class UnloadedWeight extends Constraint
{
    public string $message = "international.action.unloaded.weight-too-large";
    public string $minMessage = "international.action.unloaded.weight-more-than-one";
    public string $maxMessage = 'international.action.unloaded.weight-positive';

    public function __construct(
        string $message = null,
        string $minMessage = null,
        string $maxMessage = null,
        array  $groups = [],
        mixed  $payload = null,
    )
    {
        parent::__construct([], $groups, $payload);

        $this->message = $message ?? $this->message;
        $this->minMessage = $minMessage ?? $this->minMessage;
        $this->maxMessage = $maxMessage ?? $this->maxMessage;
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
