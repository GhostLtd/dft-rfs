<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class TripRoute extends Constraint
{
    public string $message = "common.trip-route.not-blank";
    public string $direction;
    public string $formField = "ports";

    public function validatedBy() {
        return static::class.'Validator';
    }

    public function getTargets() {
        return self::CLASS_CONSTRAINT;
    }
}