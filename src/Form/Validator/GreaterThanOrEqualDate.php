<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 **/
class GreaterThanOrEqualDate extends GreaterThanOrEqual
{
    public $message = 'common.date.greater-than-or-equal';
}