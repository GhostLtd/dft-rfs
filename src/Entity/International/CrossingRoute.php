<?php

namespace App\Entity\International;

use App\Repository\International\CrossingRouteRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CrossingRouteRepository::class)
 * @ORM\Table(name="international_crossing_route")
 */
class CrossingRoute
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $ukPort;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $foreignPort;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUkPort(): ?string
    {
        return $this->ukPort;
    }

    public function setUkPort(string $ukPort): self
    {
        $this->ukPort = $ukPort;

        return $this;
    }

    public function getForeignPort(): ?string
    {
        return $this->foreignPort;
    }

    public function setForeignPort(string $foreignPort): self
    {
        $this->foreignPort = $foreignPort;

        return $this;
    }
}
