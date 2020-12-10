<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidValueUnit extends Constraint
{
    public $unitBlankMessage = "common.value-unit.unit.not-blank";
    public $valueBlankMessage = "common.value-unit.value.not-blank";
    public $valuePositiveMessage = "common.value-unit.value.positive-or-zero";
    public $allowBlank = false;

    public function validatedBy() {
        return static::class.'Validator';
    }
}