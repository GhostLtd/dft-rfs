<?php

namespace App\Utility\Menu;

interface MenuInterface
{
    /** @return MenuItemInterface[] */
    public function getMenuItems();
}