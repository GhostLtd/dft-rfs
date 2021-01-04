<?php

namespace App\Entity\International;

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
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     * @ORM\Column(type="guid", unique=true)
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="common.string.not-blank", groups={"add_survey"})
     * @Assert\Length(max=255, maxMessage="common.string.max-length", groups={"add_survey"})
     */
    private $businessName;

    /**
     * @ORM\OneToMany(targetEntity=PreEnquiry::class, mappedBy="company", cascade={"persist"})
     */
    private $preEnquiries;

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
        $this->preEnquiries = new ArrayCollection();
        $this->surveys = new ArrayCollection();
    }

    public function getId(): ?string
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
     * @return Collection|PreEnquiry[]
     */
    public function getPreEnquiries(): Collection
    {
        return $this->preEnquiries;
    }

    public function addPreEnquiry(PreEnquiry $preEnquiry): self
    {
        if (!$this->preEnquiries->contains($preEnquiry)) {
            $this->preEnquiries[] = $preEnquiry;
            $preEnquiry->setCompany($this);
        }

        return $this;
    }

    public function removePreEnquiry(PreEnquiry $preEnquiry): self
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
