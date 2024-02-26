<?php

namespace App\Entity\International;

use App\Repository\International\SamplingGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;

#[ORM\Entity(repositoryClass: SamplingGroupRepository::class)]
class SamplingGroup
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: Types::STRING, length: 36, unique: true, options: ['fixed' => true])]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?string $id = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $number = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $sizeGroup = null;

    /**
     * @var Collection<int, Company>
     */
    #[ORM\OneToMany(mappedBy: 'samplingGroup', targetEntity: Company::class)]
    private Collection $companies;

    public function __construct()
    {
        $this->companies = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function setNumber(int $number): self
    {
        $this->number = $number;
        return $this;
    }

    public function getSizeGroup(): ?int
    {
        return $this->sizeGroup;
    }

    public function setSizeGroup(int $sizeGroup): self
    {
        $this->sizeGroup = $sizeGroup;
        return $this;
    }

    /**
     * @return Collection<Company>
     */
    public function getCompanies(): Collection
    {
        return $this->companies;
    }

    public function addCompany(Company $company): self
    {
        if (!$this->companies->contains($company)) {
            $this->companies[] = $company;
            $company->setSamplingGroup($this);
        }

        return $this;
    }

    public function removeCompany(Company $company): self
    {
        if ($this->companies->removeElement($company)) {
            // set the owning side to null (unless already changed)
            if ($company->getSamplingGroup() === $this) {
                $company->setSamplingGroup(null);
            }
        }

        return $this;
    }
}
