<?php

namespace App\Form\Validator;

use App\Entity\Address;
use App\Entity\ValueUnitInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ValidAddressValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof ValidAddress) {
            throw new UnexpectedTypeException($constraint, ValidAddress::class);
        }

        if (!$value) {
            return;
        }

        if (!$value instanceof Address) {
            throw new UnexpectedValueException($value, Address::class);
        }

        $validator = $this->context->getValidator()->inContext($this->context);

        $validator->atPath('line1')->validate($value->getLine1(), [
            new NotBlank(['message' => $constraint->line1BlankMessage]),
        ], ['Default']);

        $validator->atPath('postcode')->validate($value->getPostcode(), [
            new NotBlank(['message' => $constraint->postcodeBlankMessage]),
        ], ['Default']);
    }
}