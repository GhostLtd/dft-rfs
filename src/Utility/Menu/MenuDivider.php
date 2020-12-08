<?php

namespace App\Utility\Menu;

class MenuDivider extends MenuItem
{
    public function __construct()
    {
        parent::__construct(null, null, null, []);
    }
}