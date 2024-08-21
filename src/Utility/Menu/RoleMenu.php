<?php

namespace App\Utility\Menu;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class RoleMenu implements MenuInterface
{
    use RoleFilterTrait;

    /**
     * @var MenuItemInterface[]
     */
    protected $menuItems;

    /**
     * @param array<RoleMenuItem> $roleMenuItems
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker, array $roleMenuItems)
    {
        $this->menuItems = $this->filterMenuItemsByRole($authorizationChecker, $roleMenuItems);
    }

    /**
     * @return MenuItemInterface[]
     */
    #[\Override]
    public function getMenuItems()
    {
        return $this->menuItems;
    }
}