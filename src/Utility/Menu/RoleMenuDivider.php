<?php

namespace App\Utility\Menu;

class RoleMenuDivider extends RoleMenuItem
{
    public function __construct(array $roles)
    {
        parent::__construct(null, null, null, [], $roles);
    }
}