<?php

namespace App\Entity\International;

use App\Entity\IdTrait;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Repository\International\CompanyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=CompanyRepository::class)
 * @ORM\Table(name="international_company")
 */
class Company
{
    use IdTrait;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="common.string.not-blank", groups={"add_survey", "import_survey"})
     * @Assert\Length(max=255, maxMessage="common.string.max-length", groups={"add_survey", "import_survey"})
     */
    private $businessName;

    /**
     * @ORM\OneToMany(targetEntity=Survey::class, mappedBy="company", cascade={"persist"})
     */
    private $surveys;

    /**
     * @ORM\ManyToOne(targetEntity=SamplingGroup::class, inversedBy="companies")
     * @ORM\JoinColumn(nullable=true)
     */
    private $samplingGroup;

    public function __construct()
    {
        $this->surveys = new ArrayCollection();
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
     * @return Collection|Survey[]
     */
    public function getSurveys(): Collection
    {
        return $this->surveys;
    }

    public function addSurvey(Survey $survey): self
    {
        if (!$this->surveys->contains($survey)) {
            $this->surveys[] = $survey;
            $survey->setCompany($this);
        }

        return $this;
    }

    public function removeSurvey(Survey $survey): self
    {
        if ($this->surveys->removeElement($survey)) {
            // set the owning side to null (unless already changed)
            if ($survey->getCompany() === $this) {
                $survey->setCompany(null);
            }
        }

        return $this;
    }

    public function getSamplingGroup(): ?SamplingGroup
    {
        return $this->samplingGroup;
    }

    public function setSamplingGroup(?SamplingGroup $samplingGroup): self
    {
        $this->samplingGroup = $samplingGroup;

        return $this;
    }
}
