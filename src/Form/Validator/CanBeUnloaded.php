<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class CanBeUnloaded extends Constraint
{
    public $message = "international.action.unloading.must-load-first";

    public function validatedBy() : string {
        return static::class.'Validator';
    }

    public function getTargets(): string|array {
        return self::CLASS_CONSTRAINT;
    }
}