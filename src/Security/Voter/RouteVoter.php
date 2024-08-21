<?php

namespace App\Security\Voter;

use App\Entity\Route\Route;
use App\Utility\RoRo\PortAndRouteUsageHelper;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class RouteVoter extends Voter
{
    public const CAN_EDIT_ROUTE = "CAN_EDIT_ROUTE";
    public const CAN_DELETE_ROUTE = "CAN_DELETE_ROUTE";

    public function __construct(protected Security $security, protected PortAndRouteUsageHelper $portAndRouteUsageHelper)
    {
    }

    #[\Override]
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
    #[\Override]
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
