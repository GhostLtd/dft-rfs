<?php

namespace App\Form\Validator;

use App\Entity\International\Action;
use App\Repository\International\ActionRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class CanBeUnloadedValidator extends ConstraintValidator
{
    protected $actionRepository;

    public function __construct(ActionRepository $actionRepository)
    {
        $this->actionRepository = $actionRepository;
    }

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof CanBeUnloaded) {
            throw new UnexpectedTypeException($constraint, CanBeUnloaded::class);
        }

        if (!$value) {
            return;
        }

        if (!$value instanceof Action) {
            throw new UnexpectedValueException($value, Action::class);
        }

        $trip = $value->getTrip();

        if (!$trip || !$trip->getId()) {
            throw new InvalidArgumentException('international.action.unloading.bad-trip');
        }

        if ($value->getLoading() === false) {
            $loadingActions = $this->actionRepository->getLoadingActions($trip->getId());

            if (empty($loadingActions)) {
                $this->context->buildViolation($constraint->message)->atPath('loading')->addViolation();
            }
        }
    }
}