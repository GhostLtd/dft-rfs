<?php

namespace App\Form\Validator;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class OtherNotBlankValidator extends ConstraintValidator
{
    #[\Override]
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof OtherNotBlank) {
            throw new UnexpectedTypeException($constraint, OtherNotBlank::class);
        }

        $accessor = PropertyAccess::createPropertyAccessor();
        $selectValue = $accessor->getValue($value, $constraint->selectField);
        $otherField = $constraint->otherField ?? "{$constraint->selectField}Other";
        $otherValue = $accessor->getValue($value, $otherField);

        if (is_array($selectValue)) {
            if (!in_array($constraint->triggerValue, $selectValue)) {
                return;
            }
        } else if ($selectValue !== $constraint->triggerValue) {
            return;
        }

        if (!$otherValue) {
            $this->context
                ->buildViolation($constraint->message)
                ->atPath($otherField)
                ->addViolation();
        }
    }
}
