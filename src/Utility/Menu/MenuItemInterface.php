<?php

namespace App\Utility\Menu;

interface MenuItemInterface
{
    /**
     * @return MenuItemInterface[]
     */
    public function getChildren();

    /**
     * @return string
     */
    public function getId();

    /**
     * @return string
     */
    public function getTitle();

    /**
     * @return string
     */
    public function getUrl();

    /**
     * @return bool
     */
    public function hasChildren();

    /**
     * @return string
     */
    public function getOptions();
}