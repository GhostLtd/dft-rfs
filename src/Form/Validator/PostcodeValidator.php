<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class PostcodeValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
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

        $regex = '/^[A-Z]{1,2}\d[A-Z0-9]? ?\d[A-Z]{2}$/';

        if (!preg_match($regex, $value)) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}