<?php

namespace App\Security\Voter\RoRo;

use App\Entity\RoRo\Operator;
use App\Utility\RoRo\OperatorUsageHelper;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class OperatorVoter extends Voter
{
    const CAN_EDIT_OPERATOR = "CAN_EDIT_OPERATOR";
    const CAN_DELETE_OPERATOR = "CAN_DELETE_OPERATOR";

    protected OperatorUsageHelper $operatorUsageHelper;
    protected Security $security;

    public function __construct(OperatorUsageHelper $operatorUsageHelper, Security $security)
    {
        $this->operatorUsageHelper = $operatorUsageHelper;
        $this->security = $security;
    }

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
