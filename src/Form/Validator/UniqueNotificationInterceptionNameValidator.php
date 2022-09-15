<?php

namespace App\Form\Validator;

use App\Entity\NotificationInterceptionAdvancedInterface;
use App\Utility\NotificationInterceptionService;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueNotificationInterceptionNameValidator extends ConstraintValidator
{
    private NotificationInterceptionService $notificationInterceptionService;

    public function __construct(NotificationInterceptionService $notificationInterceptionService)
    {
        $this->notificationInterceptionService = $notificationInterceptionService;
    }

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof UniqueNotificationInterceptionName) {
            throw new UnexpectedTypeException($constraint, UniqueNotificationInterceptionName::class);
        }

        if (!$value instanceof NotificationInterceptionAdvancedInterface) {
            throw new UnexpectedTypeException($value, NotificationInterceptionAdvancedInterface::class);
        }

        $names = [$value->getPrimaryName(), ...array_map(fn($n) => $n->getName(), $value->getAdditionalNames()->toArray())];
        $nonUniqueNames = $this->notificationInterceptionService->getNonUniqueCompanyNames($value);

        foreach ($nonUniqueNames as $nonUniqueName) {
            $this->context->buildViolation($constraint->message, ['{{ value }}' => $nonUniqueName])->addViolation();
        }
    }
}