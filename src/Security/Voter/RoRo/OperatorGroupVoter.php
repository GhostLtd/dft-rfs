<?php

namespace App\Security\Voter\RoRo;

use App\Entity\RoRo\OperatorGroup;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class OperatorGroupVoter extends Voter
{
    public const CAN_ADD_OPERATOR_GROUP = "CAN_ADD_OPERATOR_GROUP";
    public const CAN_EDIT_OPERATOR_GROUP = "CAN_EDIT_OPERATOR_GROUP";
    public const CAN_DELETE_OPERATOR_GROUP = "CAN_DELETE_OPERATOR_GROUP";

    public function __construct(
        protected Security $security
    ) {}

    #[\Override]
    protected function supports(string $attribute, $subject): bool
    {
        return
            (
                $subject instanceof OperatorGroup &&
                in_array($attribute, [
                    self::CAN_DELETE_OPERATOR_GROUP,
                    self::CAN_EDIT_OPERATOR_GROUP,
                ])
            ) || (
                $subject === null &&
                $attribute === self::CAN_ADD_OPERATOR_GROUP
            );
    }

    /**
     * @param OperatorGroup $subject
     */
    #[\Override]
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        return $this->security->isGranted('ROLE_ADMIN_USER');
    }
}
