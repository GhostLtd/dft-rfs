<?php

namespace App\Security\Voter;

use App\Entity\Route\Route;
use App\Utility\RoRo\PortAndRouteUsageHelper;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class RouteVoter extends Voter
{
    const CAN_EDIT_ROUTE = "CAN_EDIT_ROUTE";
    const CAN_DELETE_ROUTE = "CAN_DELETE_ROUTE";

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
            $subject instanceof Route &&
            in_array($attribute, [
                self::CAN_DELETE_ROUTE,
                self::CAN_EDIT_ROUTE,
            ]);
    }

    /**
     * @param Route $subject
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        if (!$this->security->isGranted('ROLE_ADMIN_USER')) {
            return false;
        }

        return match ($attribute) {
            self::CAN_DELETE_ROUTE => !$this->portAndRouteUsageHelper->isRouteInUseBySurvey($subject),
            self::CAN_EDIT_ROUTE => true, // All admin users can edit routes (although only "isActive" is editable)
            default => false,
        };
    }
}
