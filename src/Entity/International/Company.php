<?php

namespace App\Entity\International;

use App\Entity\IdTrait;
use App\Repository\International\CompanyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'international_company')]
#[ORM\Entity(repositoryClass: CompanyRepository::class)]
class Company
{
    use IdTrait;

    #[Assert\NotBlank(message: 'common.string.not-blank', groups: ['add_survey', 'import_survey'])]
    #[Assert\Length(max: 255, maxMessage: 'common.string.max-length', groups: ['add_survey', 'import_survey'])]
    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $businessName = null;

    /**
     * @var Collection<int, Survey>
     */
    #[ORM\OneToMany(mappedBy: 'company', targetEntity: Survey::class, cascade: ['persist'])]
    private Collection $surveys;

    #[ORM\JoinColumn(nullable: true)]
    #[ORM\ManyToOne(targetEntity: SamplingGroup::class, inversedBy: 'companies')]
    private ?SamplingGroup $samplingGroup = null;

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
     * @return Collection<Survey>
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
