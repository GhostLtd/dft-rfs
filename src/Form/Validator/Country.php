<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Country extends Constraint
{
    public $message = "common.country.country";
    public string $otherMessage = "common.country.other";

    public function validatedBy() : string {
        return static::class.'Validator';
    }

    public function getTargets(): string|array {
        return self::CLASS_CONSTRAINT;
    }
}