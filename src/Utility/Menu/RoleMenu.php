<?php

namespace App\Utility\Menu;

use Symfony\Bundle\SecurityBundle\Security;

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
    public function __construct(Security $security, array $roleMenuItems)
    {
        $this->menuItems = $this->filterMenuItemsByRole($security, $roleMenuItems);
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