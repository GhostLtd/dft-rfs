<?php

namespace App\Entity\Domestic;

use App\Entity\SurveyTrait;
use App\Repository\Domestic\SurveyRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SurveyRepository::class)
 */
class Survey
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
     * @ORM\OneToOne(targetEntity=SurveyResponse::class, mappedBy="survey", cascade={"persist"})
     */
    private $response;

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

    public function getResponse(): ?SurveyResponse
    {
        return $this->response;
    }

    public function setResponse(SurveyResponse $response): self
    {
        $this->response = $response;

        // set the owning side of the relation if necessary
        if ($response->getSurvey() !== $this) {
            $response->setSurvey($this);
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