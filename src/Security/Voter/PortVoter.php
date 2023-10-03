<?php

namespace App\Security\Voter;

use App\Entity\Route\PortInterface;
use App\Utility\RoRo\PortAndRouteUsageHelper;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class PortVoter extends Voter
{
    const CAN_DELETE_PORT = "CAN_DELETE_PORT";
    const CAN_EDIT_PORT = "CAN_EDIT_PORT";

    protected Security $security;
    protected PortAndRouteUsageHelper $portAndRouteUsageHelper;

    public function __construct(Security $security, PortAndRouteUsageHelper $portAndRouteUsageHelper)
    {
        $this->security = $security;
        $this->portAndRouteUsageHelper = $portAndRouteUsageHelper;
    }

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
