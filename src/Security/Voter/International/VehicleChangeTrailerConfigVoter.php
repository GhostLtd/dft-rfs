<?php

namespace App\Security\Voter\International;

use App\Entity\International\Vehicle;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class VehicleChangeTrailerConfigVoter extends Voter
{
    const EDIT_TRAILER_CONFIGS = 'EDIT_TRAILER_CONFIGS';

    protected function supports($attribute, $subject)
    {
        return in_array($attribute, [self::EDIT_TRAILER_CONFIGS])
            && $subject instanceof Vehicle;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        /** @var Vehicle $subject */
        switch ($attribute) {
            case self::EDIT_TRAILER_CONFIGS:
                return $subject->getTrips()->count() === 0;
        }

        return false;
    }
}
