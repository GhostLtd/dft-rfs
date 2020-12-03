<?php

namespace App\Form\Validator;

use App\Entity\Volume;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ValidVolumeValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof ValidVolume) {
            throw new UnexpectedTypeException($constraint, ValidVolume::class);
        }

        if (!$value) {
            return;
        }

        if (!$value instanceof Volume) {
            throw new UnexpectedValueException($value, 'Volume');
        }

        $validator = $this->context->getValidator()->inContext($this->context);

        $validator->atPath('value')->validate($value->getValue(), [
            new NotBlank(['message' => $constraint->valueBlankMessage]),
            new PositiveOrZero(['message' => $constraint->valuePositiveMessage]),
        ], ['Default']);

        $validator->atPath('unit')->validate($value->getUnit(), [
            new NotBlank(['message' => $constraint->unitBlankMessage]),
        ], ['Default']);
    }
}