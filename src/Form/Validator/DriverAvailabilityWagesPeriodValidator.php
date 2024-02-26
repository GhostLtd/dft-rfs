<?php

namespace App\Form\Validator;

use App\Entity\CurrencyOrPercentage;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class DriverAvailabilityWagesPeriodValidator extends ConstraintValidator
{
    #[\Override]
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof DriverAvailabilityWagesPeriod) {
            throw new UnexpectedTypeException($constraint, DriverAvailabilityWagesPeriod::class);
        }

        $accessor = PropertyAccess::createPropertyAccessor();
        $triggerValue = $accessor->getValue($value, $constraint->triggerField);
        $targetValue = $accessor->getValue($value, $constraint->targetField);

        if (!$triggerValue instanceof CurrencyOrPercentage) {
            return;
        }

        if ($triggerValue->getValue() !== null && !$targetValue) {
            $this->context
                ->buildViolation($constraint->message)
                ->atPath($constraint->targetField)
                ->addViolation();
        }
    }
}
