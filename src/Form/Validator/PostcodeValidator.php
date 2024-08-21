<?php

namespace App\Form\Validator;

use App\Utility\PostcodeHelper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class PostcodeValidator extends ConstraintValidator
{
    #[\Override]
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof Postcode) {
            throw new UnexpectedTypeException($constraint, Postcode::class);
        }

        if (!$value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        if (!PostcodeHelper::isValidPostcode($value)) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
