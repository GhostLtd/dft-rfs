<?php

namespace App\Security\Voter\International;

use App\Entity\International\Vehicle;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class VehicleChangeTrailerConfigVoter extends Voter
{
    public const EDIT_TRAILER_CONFIGS = 'EDIT_TRAILER_CONFIGS';

    #[\Override]
    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, [self::EDIT_TRAILER_CONFIGS])
            && $subject instanceof Vehicle;
    }

    #[\Override]
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        /** @var Vehicle $subject */
        return match ($attribute) {
            self::EDIT_TRAILER_CONFIGS => $subject->getTrips()->count() === 0,
            default => false,
        };
    }
}
