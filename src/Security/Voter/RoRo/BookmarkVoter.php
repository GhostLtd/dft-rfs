<?php

namespace App\Security\Voter\RoRo;

use App\Entity\RoRo\Survey;
use App\Entity\RoRoUser;
use App\Entity\SurveyStateInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class BookmarkVoter extends Voter
{
    public const EDIT_BOOKMARKS = 'EDIT_BOOKMARKS';
    protected function supports(string $attribute, $subject): bool
    {
        return $attribute === self::EDIT_BOOKMARKS;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        return match($attribute)
        {
            self::EDIT_BOOKMARKS =>
                $user instanceof RoRoUser &&
                $user->getOperator()->getUsers()->count() > 1,

            default => false
        };
    }
}