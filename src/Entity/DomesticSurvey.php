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

    const REMINDER_STATE_INITIAL = "initial";
    const REMINDER_STATE_NOT_WANTED = "not-wanted";
    const REMINDER_STATE_WANTED = "wanted";
    const REMINDER_STATE_SENT = "sent";

    /**
     * @ORM\Column(type="boolean")
     */
    private $isNorthernIreland;

    /**
     * @ORM\OneToOne(targetEntity=DomesticSurveyResponse::class, mappedBy="survey", cascade={"persist"})
     */
    private $surveyResponse;

    /**
     * @ORM\Column(type="string", length=10)
     */
    private $registrationMark;

    /**
     * @ORM\Column(type="string", length=12)
     */
    private $reminderState;

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

    public function getReminderState(): ?string
    {
        return $this->reminderState;
    }

    public function setReminderState(string $reminderState): self
    {
        $this->reminderState = $reminderState;

        return $this;
    }
}
