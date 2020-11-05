<?php

namespace App\Entity;

use App\Repository\DomesticSurveyRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DomesticSurveyRepository::class)
 */
class DomesticSurvey
{
    use SurveyTrait;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isNorthernIreland;

    /**
     * @ORM\OneToOne(targetEntity=DomesticSurveyResponse::class, mappedBy="survey", cascade={"persist", "remove"})
     */
    private $surveyResponse;

    public function getIsNorthernIreland(): ?bool
    {
        return $this->isNorthernIreland;
    }

    public function setIsNorthernIreland(bool $isNorthernIreland): self
    {
        $this->isNorthernIreland = $isNorthernIreland;

        return $this;
    }

    public function getSurveyResponse(): ?DomesticSurveyResponse
    {
        return $this->surveyResponse;
    }

    public function setSurveyResponse(DomesticSurveyResponse $surveyResponse): self
    {
        $this->surveyResponse = $surveyResponse;

        // set the owning side of the relation if necessary
        if ($surveyResponse->getSurvey() !== $this) {
            $surveyResponse->setSurvey($this);
        }

        return $this;
    }
}
