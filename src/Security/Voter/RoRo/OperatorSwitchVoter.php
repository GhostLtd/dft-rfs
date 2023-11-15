<?php

namespace App\Security\Voter\RoRo;

use App\Entity\RoRoUser;
use App\Utility\RoRo\OperatorSwitchHelper;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class OperatorSwitchVoter extends Voter
{
    const CAN_SWITCH_OPERATOR = "CAN_SWITCH_OPERATOR";

    public function __construct(protected OperatorSwitchHelper $operatorSwitchHelper)
    {}

    protected function supports(string $attribute, $subject): bool
    {
        return ($attribute === self::CAN_SWITCH_OPERATOR && $subject === null);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof RoRoUser) {
            return false;
        }

        return match($attribute) {
            self::CAN_SWITCH_OPERATOR => $this->operatorSwitchHelper->canSwitchOperator($user->getOperator()),
        };
    }
}