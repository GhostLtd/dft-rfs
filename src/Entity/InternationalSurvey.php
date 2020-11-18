<?php

namespace App\Entity;

use App\Repository\InternationalSurveyRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=InternationalSurveyRepository::class)
 */
class InternationalSurvey
{
    use SurveyTrait;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $referenceNumber;

    /**
     * @ORM\ManyToOne(targetEntity=InternationalCompany::class, inversedBy="surveys")
     * @ORM\JoinColumn(nullable=false)
     */
    private $company;

    /**
     * @ORM\OneToOne(targetEntity=InternationalSurveyResponse::class, mappedBy="survey", cascade={"persist"})
     */
    private $surveyResponse;

    public function getReferenceNumber(): ?string
    {
        return $this->referenceNumber;
    }

    public function setReferenceNumber(string $referenceNumber): self
    {
        $this->referenceNumber = $referenceNumber;

        return $this;
    }

    public function getCompany(): ?InternationalCompany
    {
        return $this->company;
    }

    public function setCompany(?InternationalCompany $company): self
    {
        $this->company = $company;

        return $this;
    }

    public function getSurveyResponse(): ?InternationalSurveyResponse
    {
        return $this->surveyResponse;
    }

    public function setSurveyResponse(InternationalSurveyResponse $surveyResponse): self
    {
        $this->surveyResponse = $surveyResponse;

        // set the owning side of the relation if necessary
        if ($surveyResponse->getSurvey() !== $this) {
            $surveyResponse->setSurvey($this);
        }

        return $this;
    }
}
