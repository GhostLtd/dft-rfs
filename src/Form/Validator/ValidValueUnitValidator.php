<?php

namespace App\Form\Validator;

use Ghost\GovUkFrontendBundle\Model\ValueUnitInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ValidValueUnitValidator extends ConstraintValidator
{
    #[\Override]
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidValueUnit) {
            throw new UnexpectedTypeException($constraint, ValidValueUnit::class);
        }

        if (!$value) {
            return;
        }

        if (!$value instanceof ValueUnitInterface) {
            throw new UnexpectedValueException($value, ValueUnitInterface::class);
        }

        $validator = $this->context->getValidator()->inContext($this->context);

        if (!$constraint->allowBlank || !$value->getIsBlank()) {
            $validator->atPath('value')->validate($value->getValue(), [
                new NotBlank(['message' => $constraint->valueBlankMessage]),
                new PositiveOrZero(['message' => $constraint->valuePositiveMessage]),
            ], ['Default']);

            $validator->atPath('unit')->validate($value->getUnit(), [
                new NotBlank(['message' => $constraint->unitBlankMessage]),
            ], ['Default']);
        }
    }
}
