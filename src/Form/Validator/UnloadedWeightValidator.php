<?php

namespace App\Form\Validator;

use App\Entity\International\Action;
use App\Repository\International\ActionRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class UnloadedWeightValidator extends ConstraintValidator
{
    protected $actionRepository;

    public function __construct(ActionRepository $actionRepository)
    {
        $this->actionRepository = $actionRepository;
    }

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof UnloadedWeight) {
            throw new UnexpectedTypeException($constraint, UnloadedWeight::class);
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
            $validator = $this->context->getValidator()->inContext($this->context);
            $loadingAction = $value->getLoadingAction();

            $validator->atPath('weightOfGoods')->validate($value->getWeightOfGoods(), [
                new Positive([
                    'message' => 'common.number.positive',
                ]),
                new Range([
                    'max' => 2000000000,
                    'maxMessage' => $constraint->maxMessage,
                ]),
            ], ['Default']);

            if ($validator->getViolations()->count() === 0) {
                if (!$value->getWeightUnloadedAll()) {
                    $validator->atPath('weightOfGoods')->validate($value->getWeightOfGoods(), [
                        new NotBlank(['message' => 'common.string.not-blank'])
                    ], ['Default']);

                    if ($loadingAction) {
                        if ($value->getWeightOfGoods() > $loadingAction->getWeightOfGoods()) {
                            $this->context->buildViolation($constraint->message)->atPath('weightOfGoods')->addViolation();
                        }
                    }
                } else {
                    // This bit's for the admin form.

                    // On the frontend, it should be impossible to trigger this error, as you won't get the choice to
                    // "unload all" if the load is already partially unloaded.
                    $loadingCountExcludingThisOne = $loadingAction->getUnloadingActionCountExcluding($value);

                    if ($loadingCountExcludingThisOne !== 0) {
                        $this->context->buildViolation('international.action.unloading.cannot-unload')->atPath('weightUnloadedAll')->addViolation();
                    }
                }
            }
        }
    }
}