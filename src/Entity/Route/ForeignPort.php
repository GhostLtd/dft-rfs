<?php

namespace App\Entity\Route;

use App\Entity\IdTrait;
use App\Repository\Route\ForeignPortRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=ForeignPortRepository::class)
 * @ORM\Table(name="route_foreign_port")
 * @UniqueEntity(
 *     fields={"name"},
 *     message="ports.duplicate-name",
 *     groups={"admin_add_port", "admin_edit_port"}
 * )
 * @UniqueEntity(
 *     fields={"code"},
 *     message="ports.duplicate-code",
 *     groups={"admin_add_port", "admin_edit_port"}
 * )
 */
class ForeignPort implements PortInterface
{
    use IdTrait;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Assert\NotNull(message="ports.name.not-null", groups={"admin_add_port", "admin_edit_port"})
     */
    private ?string $name = null;

    /**
     * @ORM\Column(type="smallint", unique=true)
     * @Assert\NotNull(message="ports.code.not-null", groups={"admin_add_port", "admin_edit_port"})
     */
    private ?int $code = null;

    /**
     * @ORM\OneToMany(targetEntity=Route::class, mappedBy="foreignPort")
     */
    private Collection $routes;

    public function __construct()
    {
        $this->routes = new ArrayCollection();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getCode(): ?int
    {
        return $this->code;
    }

    public function setCode(?int $code): self
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @return Collection<int, Route>
     */
    public function getRoutes(): Collection
    {
        return $this->routes;
    }

    public function addRoute(Route $route): self
    {
        if (!$this->routes->contains($route)) {
            $this->routes[] = $route;
            $route->setForeignPort($this);
        }

        return $this;
    }

    public function removeRoute(Route $route): self
    {
        if ($this->routes->removeElement($route)) {
            // set the owning side to null (unless already changed)
            if ($route->getForeignPort() === $this) {
                $route->setForeignPort(null);
            }
        }

        return $this;
    }
}
