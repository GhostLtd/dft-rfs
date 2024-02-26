<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class Postcode extends Constraint
{
    public string $message = "common.address.postcode.invalid";

    public function __construct(
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
}
