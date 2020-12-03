<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidVolume extends Constraint
{
    public $unitBlankMessage = "common.volume.unit.not-blank";
    public $valueBlankMessage = "common.volume.value.not-blank";
    public $valuePositiveMessage = "common.volume.value.positive";

    public function validatedBy() {
        return static::class.'Validator';
    }
}