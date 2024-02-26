<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class Country extends Constraint
{
    public string $message = "common.country.country";
    public string $otherMessage = "common.country.other";

    public function __construct(
        string $message = null,
        string $otherMessage = null,
        array  $groups = [],
        mixed  $payload = null,
    )
    {
        parent::__construct([], $groups, $payload);

        $this->message = $message ?? $this->message;
        $this->otherMessage = $otherMessage ?? $this->otherMessage;
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
