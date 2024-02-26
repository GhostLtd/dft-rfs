<?php

namespace App\Form\Validator;

use App\Entity\NotificationInterceptionAdvancedInterface;
use App\Utility\NotificationInterceptionService;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueNotificationInterceptionNameValidator extends ConstraintValidator
{
    public function __construct(protected NotificationInterceptionService $notificationInterceptionService)
    {
    }

    #[\Override]
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueNotificationInterceptionName) {
            throw new UnexpectedTypeException($constraint, UniqueNotificationInterceptionName::class);
        }

        if (!$value instanceof NotificationInterceptionAdvancedInterface) {
            throw new UnexpectedTypeException($value, NotificationInterceptionAdvancedInterface::class);
        }

        // Check whether primaryName submitted is duplicated in the additionalNames submitted
        $primaryName = $value->getPrimaryName();
        $additionalNames = array_map(fn($n) => $n->getName(), $value->getAdditionalNames()->toArray());

        if (in_array($primaryName, $additionalNames)) {
            $this->context->buildViolation($constraint->message, ['{{ value }}' => $primaryName])
                ->atPath('additionalNames')
                ->addViolation();
        }

        // Check whether any of the names specified are used elsewhere (in other saved entities)
        $nonUniqueNames = $this->notificationInterceptionService->getNonUniqueCompanyNames($value);

        foreach ($nonUniqueNames as $nonUniqueName) {
            $path = ($nonUniqueName === $primaryName) ?
                'primaryName' :
                'additionalNames';

            $this->context->buildViolation($constraint->message, ['{{ value }}' => $nonUniqueName])
                ->atPath($path)
                ->addViolation();
        }
    }
}
