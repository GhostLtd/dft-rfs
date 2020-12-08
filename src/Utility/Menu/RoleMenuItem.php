<?php

namespace App\Utility\Menu;

class RoleMenuItem extends MenuItem
{
    /** @var array */
    protected $roles;

    /**
     * RoleMenuItem constructor.
     * @param string $id
     * @param string $title
     * @param string $url
     * @param array $children
     * @param array $roles
     * @param array $options
     */
    public function __construct($id, $title, $url, array $children = [], array $roles = [], $options = [])
    {
        parent::__construct($id, $title, $url, $children, $options);
        $this->roles = $roles;
    }

    /**
     * @return array
     */
    public function getRoles()
    {
        return $this->roles;
    }
}