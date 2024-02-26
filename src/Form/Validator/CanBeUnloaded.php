<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class CanBeUnloaded extends Constraint
{
    public string $message = "international.action.unloading.must-load-first";

    #[\Override]
    public function validatedBy() : string {
        return static::class.'Validator';
    }

    #[\Override]
    public function getTargets(): string|array {
        return self::CLASS_CONSTRAINT;
    }
}
