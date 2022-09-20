<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class TripRoute extends Constraint
{
    public $message = "common.trip-route.not-blank";
    public string $direction;
    public string $formField = "ports";

    public function validatedBy() : string {
        return static::class.'Validator';
    }

    public function getTargets(): string|array {
        return self::CLASS_CONSTRAINT;
    }
}