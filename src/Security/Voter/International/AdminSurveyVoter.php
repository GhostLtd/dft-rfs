<?php

namespace App\Security\Voter\International;

use App\Entity\International\Survey;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AdminSurveyVoter extends Voter
{
    const EDIT = 'EDIT';
    const RESET_PASSCODE = 'RESET_PASSCODE';
    const TRANSITION = 'TRANSITION';

    protected function supports($attribute, $subject)
    {
        return in_array($attribute, [self::EDIT, self::RESET_PASSCODE, self::TRANSITION])
            && $subject instanceof Survey;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $closedStatuses = [
            Survey::STATE_APPROVED,
            Survey::STATE_CLOSED,
            Survey::STATE_EXPORTED,
            Survey::STATE_REJECTED,
        ];

        /** @var $subject Survey */
        $state = $subject->getState();

        switch ($attribute) {
            case self::EDIT:
            case self::RESET_PASSCODE:
                return !in_array($state, $closedStatuses);
            case self::TRANSITION:
                return $state !== Survey::STATE_EXPORTED;
        }

        return false;
    }
}
