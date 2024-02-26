<?php

namespace App\Security\Voter\RoRo;

use App\Entity\RoRo\Operator;
use App\Utility\RoRo\OperatorUsageHelper;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class OperatorVoter extends Voter
{
    public const CAN_EDIT_OPERATOR = "CAN_EDIT_OPERATOR";
    public const CAN_DELETE_OPERATOR = "CAN_DELETE_OPERATOR";

    public function __construct(protected OperatorUsageHelper $operatorUsageHelper, protected Security $security)
    {
    }

    #[\Override]
    protected function supports(string $attribute, $subject): bool
    {
        return
            $subject instanceof Operator &&
            in_array($attribute, [
                self::CAN_DELETE_OPERATOR,
                self::CAN_EDIT_OPERATOR,
            ]);
    }

    /**
     * @param Operator $subject
     */
    #[\Override]
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        if (!$this->security->isGranted('ROLE_ADMIN_USER')) {
            return false;
        }

        return match ($attribute) {
            self::CAN_DELETE_OPERATOR => !$this->operatorUsageHelper->hasOperatorSubmittedSurveys($subject),
            self::CAN_EDIT_OPERATOR => true, // All admin users can edit operators
            default => false,
        };
    }
}
