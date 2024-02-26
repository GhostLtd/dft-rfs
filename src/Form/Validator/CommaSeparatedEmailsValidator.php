<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\EmailValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class CommaSeparatedEmailsValidator extends EmailValidator
{
    public function __construct(string $defaultMode = Email::VALIDATION_MODE_HTML5)
    {
        parent::__construct($defaultMode);
    }

    #[\Override]
    public function validate($value, Constraint $constraint): void
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
