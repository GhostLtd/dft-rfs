<?php

namespace App\Entity;

use App\Repository\SamplingGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SamplingGroupRepository::class)
 */
class SamplingGroup
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="smallint")
     */
    private $number;

    /**
     * @ORM\Column(type="smallint")
     */
    private $sizeGroup;

    /**
     * @ORM\OneToMany(targetEntity=InternationalCompany::class, mappedBy="samplingGroup")
     */
    private $companies;

    public function __construct()
    {
        $this->companies = new ArrayCollection();
    }

    public function getId(): ?int
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
     * @return Collection|InternationalCompany[]
     */
    public function getCompanies(): Collection
    {
        return $this->companies;
    }

    public function addCompany(InternationalCompany $company): self
    {
        if (!$this->companies->contains($company)) {
            $this->companies[] = $company;
            $company->setSamplingGroup($this);
        }

        return $this;
    }

    public function removeCompany(InternationalCompany $company): self
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
