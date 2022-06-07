<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraints\Email;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class CommaSeparatedEmails extends Email
{
    public function validatedBy()
    {
        return CommaSeparatedEmailsValidator::class;
    }
}