<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ValidRegistration extends Constraint
{
    public string $validMessage = "common.vehicle.vehicle-registration.valid";
    public string $alreadyExistsMessage = "common.vehicle.vehicle-registration.not-exists";

    public function __construct(
        string $validMessage = null,
        string $alreadyExistsMessage = null,
        array  $groups = [],
        mixed  $payload = null,
    )
    {
        parent::__construct([], $groups, $payload);

        $this->alreadyExistsMessage = $alreadyExistsMessage ?? $this->alreadyExistsMessage;
        $this->validMessage = $validMessage ?? $this->validMessage;
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
