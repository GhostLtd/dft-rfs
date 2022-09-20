<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UnloadedWeight extends Constraint
{
    public $message = "international.action.unloaded.weight-too-large";
    public $minMessage = "international.action.unloaded.weight-more-than-one";
    public $maxMessage = 'international.action.unloaded.weight-positive';

    public function validatedBy() : string {
        return static::class.'Validator';
    }

    public function getTargets(): string|array {
        return self::CLASS_CONSTRAINT;
    }
}