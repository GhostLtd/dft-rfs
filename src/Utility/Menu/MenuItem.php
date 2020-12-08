<?php

namespace App\Utility\Menu;

class MenuItem implements MenuItemInterface
{
    /**
     * @var MenuItemInterface[]
     */
    protected $children;

    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $url;

    /**
     * @var array
     */
    protected $options;

    /**
     * @param string $id
     * @param string $title
     * @param string $url
     * @param MenuItemInterface[] $children
     * @param array $options
     */
    public function __construct($id, $title, $url, array $children = [], $options = [])
    {
        $this->id = $id;
        $this->title = $title;
        $this->url = $url;
        $this->children = $children;
        $this->options = $options;
    }

    /**
     * @return MenuItemInterface[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return bool
     */
    public function hasChildren()
    {
        return is_array($this->children) && !empty($this->children);
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }
}