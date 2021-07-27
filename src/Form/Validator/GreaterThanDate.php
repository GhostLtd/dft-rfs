<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraints\GreaterThan;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 **/
class GreaterThanDate extends GreaterThan
{
    public $message = 'common.date.greater-than';
}