<?php

namespace App\Entity\Route;

use Doctrine\Common\Collections\Collection;

interface PortInterface
{
    public function getId(): ?string;

    public function getName(): ?string;
    public function setName(string $name): self;
    public function getCode(): ?int;
    public function setCode(int $code): self;

    /**
     * @return Collection<int, Route>
     */
    public function getRoutes(): Collection;
    public function addRoute(Route $route): self;
    public function removeRoute(Route $route): self;
}