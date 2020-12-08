<?php

namespace App\Utility\Menu;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

trait RoleFilterTrait
{
    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param MenuItemInterface[] $menuItems
     * @return MenuItemInterface[]
     */
    protected function filterMenuItemsByRole(AuthorizationCheckerInterface $authorizationChecker, array $menuItems)
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
                        if ($authorizationChecker->isGranted($role)) {
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
                        $this->filterMenuItemsByRole($authorizationChecker, $menuItem->getChildren()),
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
                    $this->filterMenuItemsByRole($authorizationChecker, $menuItem->getChildren()),
                    $menuItem->getOptions()
                );
            }
        }

        return $filteredMenuItems;
    }
}