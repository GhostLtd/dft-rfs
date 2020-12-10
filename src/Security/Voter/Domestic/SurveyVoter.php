<?php

namespace App\Security\Voter\Domestic;

use App\Entity\Domestic\Survey;
use App\Entity\PasscodeUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class SurveyVoter extends Voter
{
    const EDIT = 'EDIT';
    const VIEW_SUBMISSION_SUMMARY = 'VIEW_SUBMISSION_SUMMARY';
    protected function supports($attribute, $subject)
    {
        return in_array($attribute, [self::EDIT, self::VIEW_SUBMISSION_SUMMARY])
            && $subject instanceof Survey;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        /** @var PasscodeUser $user */
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }


        switch ($attribute) {
            case self::EDIT:
                return $user->getDomesticSurvey()->getState() === Survey::STATE_IN_PROGRESS;

            case self::VIEW_SUBMISSION_SUMMARY:
                return $user->getDomesticSurvey()->getState() === Survey::STATE_CLOSED;
        }

        return false;
    }
}
