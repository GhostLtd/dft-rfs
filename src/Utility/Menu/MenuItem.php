<?php

namespace App\Utility\Menu;

class MenuItem implements MenuItemInterface
{
    /**
     * @var MenuItemInterface[]
     */
    protected $children;

    /**
     * @param string $id
     * @param string $title
     * @param string $url
     * @param MenuItemInterface[] $children
     * @param array $options
     */
    public function __construct(protected $id, protected $title, protected $url, array $children = [], protected $options = [])
    {
        $this->children = $children;
    }

    /**
     * @return MenuItemInterface[]
     */
    #[\Override]
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @return string
     */
    #[\Override]
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    #[\Override]
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    #[\Override]
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return bool
     */
    #[\Override]
    public function hasChildren()
    {
        return is_array($this->children) && !empty($this->children);
    }

    /**
     * @return array
     */
    #[\Override]
    public function getOptions()
    {
        return $this->options;
    }
}