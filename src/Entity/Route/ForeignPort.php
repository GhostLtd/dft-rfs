<?php

namespace App\Entity\Route;

use App\Entity\IdTrait;
use App\Repository\Route\ForeignPortRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[UniqueEntity(fields: ['name'], message: 'ports.duplicate-name', groups: ['admin_add_port', 'admin_edit_port'])]
#[UniqueEntity(fields: ['code'], message: 'ports.duplicate-code', groups: ['admin_add_port', 'admin_edit_port'])]
#[ORM\Table(name: 'route_foreign_port')]
#[ORM\Entity(repositoryClass: ForeignPortRepository::class)]
class ForeignPort implements PortInterface
{
    use IdTrait;

    #[Assert\NotNull(message: 'ports.name.not-null', groups: ['admin_add_port', 'admin_edit_port'])]
    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    private ?string $name = null;

    #[Assert\NotNull(message: 'ports.code.not-null', groups: ['admin_add_port', 'admin_edit_port'])]
    #[Assert\Positive(message: 'common.number.positive', groups: ['admin_add_port', 'admin_edit_port'])]
    #[Assert\Range(maxMessage: 'common.number.max', max: 65000, groups: ['admin_add_port', 'admin_edit_port'])]
    #[ORM\Column(type: Types::SMALLINT, unique: true)]
    private ?int $code = null;

    /**
     * @var Collection<int, Route>
     */
    #[ORM\OneToMany(mappedBy: 'foreignPort', targetEntity: Route::class)]
    private Collection $routes;

    public function __construct()
    {
        $this->routes = new ArrayCollection();
    }

    #[\Override]
    public function getName(): ?string
    {
        return $this->name;
    }

    #[\Override]
    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    #[\Override]
    public function getCode(): ?int
    {
        return $this->code;
    }

    #[\Override]
    public function setCode(?int $code): self
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @return Collection<int, Route>
     */
    #[\Override]
    public function getRoutes(): Collection
    {
        return $this->routes;
    }

    #[\Override]
    public function addRoute(Route $route): self
    {
        if (!$this->routes->contains($route)) {
            $this->routes[] = $route;
            $route->setForeignPort($this);
        }

        return $this;
    }

    #[\Override]
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
