<?php

namespace App\Utility\Menu;

class RoleMenuItem extends MenuItem
{
    /** @var string[] */
    protected array $roles;

    /**
     * @param MenuItemInterface[] $children
     * @param string[] $roles
     */
    public function __construct(?string $id, ?string $title, ?string $url, array $children = [], array $roles = [], array $options = [])
    {
        parent::__construct($id, $title, $url, $children, $options);
        $this->roles = $roles;
    }

    /**
     * @return string[]
     */
    public function getRoles(): array
    {
        return $this->roles;
    }
}