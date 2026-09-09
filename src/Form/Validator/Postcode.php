<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class Postcode extends Constraint
{
    public string $message = "common.address.postcode.invalid";

    public function __construct(
        ?string $message = null,
        public bool $allowPartial = false,
        /** Also require the outward code to be a known UK postcode district (subject to Features::OUTWARD_CODE_CHECK) */
        public bool $checkOutwardCode = false,
        array  $groups = [],
        mixed  $payload = null,
    )
    {
        parent::__construct(null, $groups, $payload);

        $this->message = $message ?? $this->message;
    }

    #[\Override]
    public function validatedBy(): string
    {
        return static::class . 'Validator';
    }
}
