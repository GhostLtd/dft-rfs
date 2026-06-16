<?php

namespace App\Utility\Menu;

interface MenuItemInterface
{
    /**
     * @return MenuItemInterface[]
     */
    public function getChildren(): array;

    public function getId(): ?string;

    public function getTitle(): ?string;

    public function getUrl(): ?string;

    public function hasChildren(): bool;

    public function getOptions(): array;
}