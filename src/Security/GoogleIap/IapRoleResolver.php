<?php

namespace App\Security\GoogleIap;

use App\Security\ManagementUserHelper;
use Ghost\GovUkCoreBundle\Security\GoogleIap\RoleResolverInterface;

class IapRoleResolver implements RoleResolverInterface
{
    public function __construct(private ManagementUserHelper $managementUserHelper)
    {}

    #[\Override]
    public function getRolesForEmailAddress(string $emailAddress): array
    {
        return [
            $this->managementUserHelper->isManagementDomain($emailAddress)
                ? 'ROLE_ADMIN_IAP_MANAGER'
                : 'ROLE_ADMIN_IAP_USER',
        ];
    }
}
