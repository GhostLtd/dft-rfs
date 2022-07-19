<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UniqueNotificationInterceptionName extends Constraint
{
    public string $message = "Company name '{{ value }}' has already been used";

    public function validatedBy(): string {
        return static::class.'Validator';
    }

    public function getTargets(): string {
        return self::CLASS_CONSTRAINT;
    }
}