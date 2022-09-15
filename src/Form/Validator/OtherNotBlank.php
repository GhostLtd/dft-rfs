<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class OtherNotBlank extends Constraint
{
    public string $message = 'This value should not be blank.';
    public string $selectField;
    public ?string $otherField = null;
    public string $triggerValue = "other";

    public function validatedBy() {
        return static::class.'Validator';
    }

    public function getTargets() {
        return self::CLASS_CONSTRAINT;
    }
}