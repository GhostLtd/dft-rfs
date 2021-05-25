<?php

namespace App\Security\Voter;

use App\Entity\SurveyInterface;
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
            && $subject instanceof SurveyInterface;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $closedStatuses = [
            SurveyInterface::STATE_APPROVED,
            SurveyInterface::STATE_CLOSED,
            SurveyInterface::STATE_EXPORTING,
            SurveyInterface::STATE_EXPORTED,
            SurveyInterface::STATE_REJECTED,
        ];

        /** @var $subject SurveyInterface */
        $state = $subject->getState();

        switch ($attribute) {
            case self::EDIT:
            case self::RESET_PASSCODE:
                return !in_array($state, $closedStatuses);
            case self::TRANSITION:
                return !in_array($state, [SurveyInterface::STATE_EXPORTING, SurveyInterface::STATE_EXPORTED]);
        }

        return false;
    }
}
