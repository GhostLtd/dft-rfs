<?php

namespace App\Entity\RoRo;

use App\Entity\IdTrait;
use App\Entity\RoRoUser;
use App\Entity\Route\Route;
use App\Repository\RoRo\OperatorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=OperatorRepository::class)
 * @ORM\Table(name="roro_operator")
 * @UniqueEntity(
 *     fields={"name"},
 *     message="operators.duplicate-name",
 *     groups={"admin_add_operator", "admin_edit_operator"}
 * )
 * @UniqueEntity(
 *     fields={"code"},
 *     message="operators.duplicate-code",
 *     groups={"admin_add_operator", "admin_edit_operator"}
 * )
 */
class Operator
{
    use IdTrait;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotNull(message="operators.name.not-null", groups={"admin_add_operator", "admin_edit_operator"})
     */
    private ?string $name = null;

    /**
     * @ORM\ManyToMany(targetEntity=Route::class, inversedBy="roroOperators")
     * @ORM\JoinTable(name="roro_operator_route")
     * @var Collection<Route>
     */
    private Collection $routes;

    /**
     * @ORM\OneToMany(targetEntity=RoRoUser::class, mappedBy="operator", orphanRemoval=true)
     * @var Collection<RoRoUser>
     */
    private Collection $users;

    /**
     * @ORM\Column(type="smallint")
     * @Assert\NotNull(message="operators.code.not-null", groups={"admin_add_operator", "admin_edit_operator"})
     */
    private ?int $code = null;

    /**
     * @ORM\Column(type="boolean", nullable=false)
     * @Assert\NotNull(message="operators.is-active.not-null", groups={"admin_add_operator", "admin_edit_operator"})
     */
    private ?bool $isActive = true;

    public function __construct()
    {
        $this->routes = new ArrayCollection();
        $this->users = new ArrayCollection();
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
        }

        return $this;
    }

    public function removeRoute(Route $route): self
    {
        $this->routes->removeElement($route);

        foreach($this->users as $user) {
            $user->removeBookmarkedRoute($route);
        }

        return $this;
    }

    /**
     * @return Collection<int, RoRoUser>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(RoRoUser $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
            $user->setOperator($this);
        }

        return $this;
    }

    public function removeUser(RoRoUser $user): self
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getOperator() === $this) {
                $user->setOperator(null);
            }
        }

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

    public function isIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(?bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }
}
