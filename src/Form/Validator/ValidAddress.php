<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidAddress extends Constraint
{
    public $line1BlankMessage = "common.address.line-1.not-blank";
    public $postcodeBlankMessage = "common.address.postcode.not-blank";

    public function validatedBy() {
        return static::class.'Validator';
    }
}