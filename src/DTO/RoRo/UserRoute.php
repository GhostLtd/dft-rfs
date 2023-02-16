<?php

namespace App\DTO\RoRo;

use App\Entity\Route\Route;

class UserRoute
{
    protected Route $route;
    protected bool $isBookmarked;

    public function __construct(Route $route, bool $isBookmarked)
    {
        $this->route = $route;
        $this->isBookmarked = $isBookmarked;
    }

    public function getRoute(): Route
    {
        return $this->route;
    }

    public function isBookmarked(): bool
    {
        return $this->isBookmarked;
    }
}