<?php

namespace App\Entity\Route;

use App\Entity\IdTrait;
use App\Entity\RoRo\Operator;
use App\Entity\RoRo\Survey;
use App\Repository\Route\RouteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=RouteRepository::class)
 * @ORM\Table(name="route")
 * @UniqueEntity(
 *     fields={"ukPort", "foreignPort"},
 *     errorPath="ports",
 *     message="routes.duplicate-route",
 *     groups={"admin_add_route"}
 * )
 */
class Route
{
    use IdTrait;

    /**
     * @ORM\ManyToOne(targetEntity=UkPort::class, inversedBy="routes")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotNull(message="common.choice.not-null", groups={"admin_add_route"})
     */
    private ?UkPort $ukPort = null;

    /**
     * @ORM\ManyToOne(targetEntity=ForeignPort::class, inversedBy="routes")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotNull(message="common.choice.not-null", groups={"admin_add_route"})
     */
    private ?ForeignPort $foreignPort = null;

    /**
     * @ORM\ManyToMany(targetEntity=Operator::class, mappedBy="routes")
     * @var Collection<Operator>
     */
    private Collection $roroOperators;

    /**
     * @ORM\OneToMany(targetEntity=Survey::class, mappedBy="route")
     * @var Collection<Survey>
     */
    private Collection $surveys;


    /**
     * @ORM\Column(type="boolean")
     * @Assert\NotNull(message="common.choice.not-null", groups={"admin_add_route", "admin_edit_route"})
     */
    private ?bool $isActive = true;

    public function __construct()
    {
        $this->roroOperators = new ArrayCollection();
        $this->surveys = new ArrayCollection();
    }

    public function getUkPort(): ?UkPort
    {
        return $this->ukPort;
    }

    public function setUkPort(?UkPort $ukPort): self
    {
        $this->ukPort = $ukPort;
        return $this;
    }

    public function getForeignPort(): ?ForeignPort
    {
        return $this->foreignPort;
    }

    public function setForeignPort(?ForeignPort $foreignPort): self
    {
        $this->foreignPort = $foreignPort;
        return $this;
    }

    /**
     * @return Collection<int, Operator>
     */
    public function getRoroOperators(): Collection
    {
        return $this->roroOperators;
    }

    public function addRoroOperator(Operator $roroOperator): self
    {
        if (!$this->roroOperators->contains($roroOperator)) {
            $this->roroOperators[] = $roroOperator;
            $roroOperator->addRoute($this);
        }

        return $this;
    }

    public function removeRoroOperator(Operator $roroOperator): self
    {
        if ($this->roroOperators->removeElement($roroOperator)) {
            $roroOperator->removeRoute($this);
        }

        return $this;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(?bool $isActive): self
    {
        $this->isActive = $isActive;

        if ($this->isActive === false) {
            foreach ($this->roroOperators as $operator) {
                $operator->removeRoute($this);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Survey>
     */
    public function getSurvey(): Collection
    {
        return $this->surveys;
    }

    public function addSurvey(Survey $survey): self
    {
        if (!$this->surveys->contains($survey)) {
            $this->surveys[] = $survey;
            $survey->setRoute($this);
        }

        return $this;
    }

    public function removeSurvey(Survey $survey): self
    {
        if ($this->surveys->removeElement($survey)) {
            // set the owning side to null (unless already changed)
            if ($survey->getRoute() === $this) {
                $survey->setRoute(null);
            }
        }

        return $this;
    }

    // -----

    public function getRouteCode(): string
    {
        return $this->ukPort->getCode().$this->foreignPort->getCode();
    }
}
