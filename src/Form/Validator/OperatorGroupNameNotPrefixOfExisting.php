<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 **/
class OperatorGroupNameNotPrefixOfExisting extends Constraint
{
    public string $message = "operator-groups.name.prefix";

    public function validatedBy() : string {
        return static::class.'Validator';
    }
}