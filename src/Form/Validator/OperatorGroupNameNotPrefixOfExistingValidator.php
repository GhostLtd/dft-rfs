<?php

namespace App\Form\Validator;

use App\Repository\RoRo\OperatorGroupRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class OperatorGroupNameNotPrefixOfExistingValidator extends ConstraintValidator
{
    public function __construct(
        protected OperatorGroupRepository $operatorGroupRepository
    ) {}

    #[\Override]
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof OperatorGroupNameNotPrefixOfExisting) {
            throw new UnexpectedTypeException($constraint, OperatorGroupNameNotPrefixOfExisting::class);
        }

        if ($value === null) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        if ($this->context->getViolations()->count() > 0) {
            return;
        }

        if ($this->operatorGroupRepository->isNamePrefixAlreadyInUse($value, $this->context->getObject())) {
            $this->context->addViolation($constraint->message);
        }
    }
}
