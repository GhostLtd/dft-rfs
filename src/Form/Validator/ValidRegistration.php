<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidRegistration extends Constraint
{
    public $validMessage = "common.vehicle.vehicle-registration.valid";
    public $alreadyExistsMessage = "common.vehicle.vehicle-registration.not-exists";

    public function validatedBy() {
        return static::class.'Validator';
    }

    public function getTargets() {
        return self::CLASS_CONSTRAINT;
    }
}