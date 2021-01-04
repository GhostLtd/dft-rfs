<?php

namespace App\Form\Validator;

use App\Entity\Address;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
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

        $lengthValidator = new Length(['max' => 255, 'maxMessage' => $constraint->maxLengthMessage]);

        if (!$constraint->allowBlank || $value->isFilled()) {
            $validator->atPath('line1')->validate($value->getLine1(), [
                new NotBlank(['message' => $constraint->line1BlankMessage]),
                $lengthValidator,
            ], ['Default']);

            $propertyAccessor = PropertyAccess::createPropertyAccessor();
            foreach(['line2', 'line3', 'line4'] as $line) {
                $validator->atPath($line)->validate($propertyAccessor->getValue($value, $line), [
                    $lengthValidator
                ], ['Default']);
            }

            $postcodeValidators = [
                new NotBlank(['message' => $constraint->postcodeBlankMessage]),
            ];

            if ($constraint->validatePostcode) {
                $postcodeValidators[] = new Postcode();
            } else {
                $postcodeValidators[] = new Length(['max' => 10, 'maxMessage' => $constraint->maxLengthMessage]);
            }

            $validator->atPath('postcode')->validate($value->getPostcode(), $postcodeValidators, ['Default']);
        }
    }
}