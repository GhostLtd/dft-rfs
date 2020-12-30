<?php

namespace App\Utility\Menu;

use Symfony\Component\Security\Core\Security;

trait RoleFilterTrait
{
    /**
     * @param Security $security
     * @param MenuItemInterface[] $menuItems
     * @return MenuItemInterface[]
     */
    protected function filterMenuItemsByRole(Security $security, array $menuItems)
    {
        $filteredMenuItems = [];

        foreach($menuItems as $menuItem)
        {
            if ($menuItem instanceof RoleMenuItem) {
                $roles = $menuItem->getRoles();

                if (empty($roles)) {
                    $allowedAccess = true;
                } else {
                    $allowedAccess = false;

                    foreach ($roles as $role) {
                        if ($security->getToken() && $security->isGranted($role)) {
                            $allowedAccess = true;
                            break;
                        }
                    }
                }

                if ($allowedAccess) {
                    $filteredMenuItems[] = new RoleMenuItem(
                        $menuItem->getId(),
                        $menuItem->getTitle(),
                        $menuItem->getUrl(),
                        $this->filterMenuItemsByRole($security, $menuItem->getChildren()),
                        $menuItem->getRoles(),
                        $menuItem->getOptions()
                    );
                }
            } else {
                // Sometimes we'll encounter menuItems that aren't RoleMenuItems and hence don't have getRoles()
                // e.g. MenuDivider
                $filteredMenuItems[] = new MenuItem(
                    $menuItem->getId(),
                    $menuItem->getTitle(),
                    $menuItem->getUrl(),
                    $this->filterMenuItemsByRole($security, $menuItem->getChildren()),
                    $menuItem->getOptions()
                );
            }
        }

        return $filteredMenuItems;
    }
}