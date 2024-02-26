<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraints\Email;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
class CommaSeparatedEmails extends Email
{
    #[\Override]
    public function validatedBy(): string
    {
        return CommaSeparatedEmailsValidator::class;
    }
}
