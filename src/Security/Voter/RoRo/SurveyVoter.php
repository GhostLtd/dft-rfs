<?php

namespace App\Security\Voter\RoRo;

use App\Entity\RoRo\Survey;
use App\Entity\SurveyStateInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SurveyVoter extends Voter
{
    public const EDIT = 'EDIT';
    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, [self::EDIT])
            && $subject instanceof Survey;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        return match($attribute)
        {
            self::EDIT => in_array($subject->getState(), [
                SurveyStateInterface::STATE_IN_PROGRESS,
                SurveyStateInterface::STATE_NEW,
            ]),

            default => false
        };
    }
}