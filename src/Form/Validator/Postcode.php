<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

class Postcode extends Constraint
{
    public $message = "common.address.postcode.invalid";

    public function validatedBy() {
        return static::class.'Validator';
    }
}