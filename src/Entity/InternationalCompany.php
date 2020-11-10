<?php

namespace App\Entity;

use App\Repository\InternationalCompanyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=InternationalCompanyRepository::class)
 */
class InternationalCompany
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
    private $businessName;

    /**
     * @ORM\OneToMany(targetEntity=InternationalPreEnquiry::class, mappedBy="company", orphanRemoval=true)
     */
    private $preEnquiries;

    /**
     * @ORM\OneToMany(targetEntity=InternationalSurvey::class, mappedBy="company", orphanRemoval=true)
     */
    private $surveys;

    public function __construct()
    {
        $this->preEnquiries = new ArrayCollection();
        $this->surveys = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBusinessName(): ?string
    {
        return $this->businessName;
    }

    public function setBusinessName(string $businessName): self
    {
        $this->businessName = $businessName;

        return $this;
    }

    /**
     * @return Collection|InternationalPreEnquiry[]
     */
    public function getPreEnquiries(): Collection
    {
        return $this->preEnquiries;
    }

    public function addPreEnquiry(InternationalPreEnquiry $preEnquiry): self
    {
        if (!$this->preEnquiries->contains($preEnquiry)) {
            $this->preEnquiries[] = $preEnquiry;
            $preEnquiry->setCompany($this);
        }

        return $this;
    }

    public function removePreEnquiry(InternationalPreEnquiry $preEnquiry): self
    {
        if ($this->preEnquiries->removeElement($preEnquiry)) {
            // set the owning side to null (unless already changed)
            if ($preEnquiry->getCompany() === $this) {
                $preEnquiry->setCompany(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|InternationalSurvey[]
     */
    public function getSurveys(): Collection
    {
        return $this->surveys;
    }

    public function addSurvey(InternationalSurvey $survey): self
    {
        if (!$this->surveys->contains($survey)) {
            $this->surveys[] = $survey;
            $survey->setCompany($this);
        }

        return $this;
    }

    public function removeSurvey(InternationalSurvey $survey): self
    {
        if ($this->surveys->removeElement($survey)) {
            // set the owning side to null (unless already changed)
            if ($survey->getCompany() === $this) {
                $survey->setCompany(null);
            }
        }

        return $this;
    }
}
