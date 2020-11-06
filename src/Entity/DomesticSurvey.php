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

    const STATE_PRE_SURVEY_INTRODUCTION = 'introduction';
    const STATE_PRE_SURVEY_REQUEST_CONTACT_DETAILS = 'request contact details';
    const STATE_PRE_SURVEY_ASK_COMPLETABLE = 'can you complete the survey?';
    const STATE_PRE_SURVEY_ASK_ON_HIRE = 'is vehicle on hire?';
//    const STATE_PRE_SURVEY_ASK_REMINDER_EMAIL = 'is reminder email required?';
    const STATE_PRE_SURVEY_SUMMARY = 'summary';
    const STATE_PRE_SURVEY_ASK_REASON_CANT_COMPLETE = "reason can't complete?" ;
    const STATE_PRE_SURVEY_ASK_HIREE_DETAILS = 'provide hiree details';

    public $choiceFormResult;

    private $state;

    /**
     * @return mixed
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param mixed $state
     */
    public function setState($state): void
    {
        $this->state = $state;
    }

    /**
     * @ORM\Column(type="boolean")
     */
    private $isNorthernIreland;

    /**
     * @ORM\OneToOne(targetEntity=DomesticSurveyResponse::class, mappedBy="survey", cascade={"persist", "remove"})
     */
    private $surveyResponse;

    /**
     * @ORM\Column(type="string", length=10)
     */
    private $registrationMark;

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

    public function getRegistrationMark(): ?string
    {
        return $this->registrationMark;
    }

    public function setRegistrationMark(string $registrationMark): self
    {
        $this->registrationMark = $registrationMark;

        return $this;
    }
}
