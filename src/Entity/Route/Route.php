<?php

namespace App\Entity\Route;

use App\Entity\IdTrait;
use App\Entity\RoRo\Operator;
use App\Entity\RoRo\Survey;
use App\Repository\Route\RouteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[UniqueEntity(fields: ['ukPort', 'foreignPort'], message: 'routes.duplicate-route', errorPath: 'ports', groups: ['admin_add_route'])]
#[ORM\Table(name: 'route')]
#[ORM\Entity(repositoryClass: RouteRepository::class)]
class Route
{
    use IdTrait;

    #[Assert\NotNull(message: 'common.choice.not-null', groups: ['admin_add_route'])]
    #[ORM\JoinColumn(nullable: false)]
    #[ORM\ManyToOne(targetEntity: UkPort::class, inversedBy: 'routes')]
    private ?UkPort $ukPort = null;

    #[Assert\NotNull(message: 'common.choice.not-null', groups: ['admin_add_route'])]
    #[ORM\JoinColumn(nullable: false)]
    #[ORM\ManyToOne(targetEntity: ForeignPort::class, inversedBy: 'routes')]
    private ?ForeignPort $foreignPort = null;

    /**
     * @var Collection<Operator>
     */
    #[ORM\ManyToMany(targetEntity: Operator::class, mappedBy: 'routes')]
    private Collection $roroOperators;

    /**
     * @var Collection<Survey>
     */
    #[ORM\OneToMany(mappedBy: 'route', targetEntity: Survey::class)]
    private Collection $surveys;


    #[Assert\NotNull(message: 'common.choice.not-null', groups: ['admin_add_route', 'admin_edit_route'])]
    #[ORM\Column(type: Types::BOOLEAN)]
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
    public function getSurveys(): Collection
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
}
