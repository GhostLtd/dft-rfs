<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UnloadedWeight extends Constraint
{
    public $message = "international.action.unloading.weight-of-goods";
    public $minMessage = "international.action.unloading.weight-more-than-one";
    public $maxMessage = 'common.number.max';

    public function validatedBy() {
        return static::class.'Validator';
    }

    public function getTargets() {
        return self::CLASS_CONSTRAINT;
    }
}