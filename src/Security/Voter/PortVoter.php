<?php

namespace App\Security\Voter;

use App\Entity\Route\PortInterface;
use App\Utility\RoRo\PortAndRouteUsageHelper;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PortVoter extends Voter
{
    public const CAN_DELETE_PORT = "CAN_DELETE_PORT";
    public const CAN_EDIT_PORT = "CAN_EDIT_PORT";

    public function __construct(protected Security $security, protected PortAndRouteUsageHelper $portAndRouteUsageHelper)
    {
    }

    #[\Override]
    protected function supports(string $attribute, $subject): bool
    {
        return
            $subject instanceof PortInterface &&
            in_array($attribute, [
                self::CAN_DELETE_PORT,
                self::CAN_EDIT_PORT,
            ]);
    }

    /**
     * @param PortInterface $subject
     */
    #[\Override]
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        if (!$this->security->isGranted('ROLE_ADMIN_USER')) {
            return false;
        }

        $isManager = $this->security->isGranted('ROLE_ADMIN_MANAGER');

        return match ($attribute) {
            // Ports can only be deleted if they're not in use either by routes or surveys
            self::CAN_DELETE_PORT => !$this->portAndRouteUsageHelper->isPortInUseBySurveyOrRoute($subject),

            // Ports can be edited:
            //      a) If they're not yet in use by a route
            //   OR b) By a manager (even if in use)
            self::CAN_EDIT_PORT => $isManager || !$this->portAndRouteUsageHelper->isPortInUseByRoute($subject),
            default => false,
        };
    }
}
