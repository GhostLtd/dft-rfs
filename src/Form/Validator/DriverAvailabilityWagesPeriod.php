<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class DriverAvailabilityWagesPeriod extends Constraint
{
    public $message = 'Choose a period for this wage increase.';
    public string $triggerField;
    public string $targetField;

    public function validatedBy() : string {
        return static::class.'Validator';
    }

    public function getTargets(): string|array {
        return self::CLASS_CONSTRAINT;
    }
}