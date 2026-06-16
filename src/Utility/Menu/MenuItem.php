<?php

namespace App\Utility\Menu;

class MenuItem implements MenuItemInterface
{
    /**
     * @var MenuItemInterface[]
     */
    protected array $children;

    /**
     * @param MenuItemInterface[] $children
     */
    public function __construct(protected ?string $id, protected ?string $title, protected ?string $url, array $children = [], protected array $options = [])
    {
        $this->children = $children;
    }

    /**
     * @return MenuItemInterface[]
     */
    #[\Override]
    public function getChildren(): array
    {
        return $this->children;
    }

    #[\Override]
    public function getId(): ?string
    {
        return $this->id;
    }

    #[\Override]
    public function getTitle(): ?string
    {
        return $this->title;
    }

    #[\Override]
    public function getUrl(): ?string
    {
        return $this->url;
    }

    #[\Override]
    public function hasChildren(): bool
    {
        return !empty($this->children);
    }

    #[\Override]
    public function getOptions(): array
    {
        return $this->options;
    }
}