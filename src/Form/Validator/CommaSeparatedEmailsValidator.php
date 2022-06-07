<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\EmailValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class CommaSeparatedEmailsValidator extends EmailValidator
{
    public function validate($value, Constraint $constraint)
    {
        if ($value === null) {
            return;
        }

        if (!is_scalar($value) && !(\is_object($value) && method_exists($value, '__toString'))) {
            throw new UnexpectedValueException($value, 'string');
        }

        $value = (string) $value;
        $emails = array_map('trim', explode(',', $value));

        $currentNumberOfViolations = $this->context->getViolations()->count();

        foreach($emails as $email) {
            parent::validate($email, $constraint);

            if ($this->context->getViolations()->count() > $currentNumberOfViolations) {
                // EmailValidator::validate added a violation, so we're done...
                break;
            }
        }
    }
}