<?php

namespace App\Form\Validator;

use App\Entity\International\Trip;
use App\Form\InternationalSurvey\Trip\AbstractPortsAndCargoStateType;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class TripRouteValidator extends ConstraintValidator
{
    #[\Override]
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof TripRoute) {
            throw new UnexpectedTypeException($constraint, TripRoute::class);
        }

        if (!$value) {
            return;
        }

        if (!$value instanceof Trip) {
            throw new UnexpectedValueException($value, Trip::class);
        }

        if (!in_array($constraint->direction, [AbstractPortsAndCargoStateType::DIRECTION_OUTBOUND, AbstractPortsAndCargoStateType::DIRECTION_RETURN])) {
            throw new InvalidOptionException('"direction" is invalid');
        }

        $accessor = PropertyAccess::createPropertyAccessor();
        $ukPort = $accessor->getValue($value, "{$constraint->direction}UkPort");
        $foreignPort = $accessor->getValue($value, "{$constraint->direction}ForeignPort");

        if ($ukPort === null || $foreignPort === null) {
            $this->context->buildViolation($constraint->message)->atPath($constraint->formField)->addViolation();
        }
    }
}
