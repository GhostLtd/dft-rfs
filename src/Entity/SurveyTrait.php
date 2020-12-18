<?php

namespace App\Entity;

use App\Form\Validator as AppAssert;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

trait SurveyTrait {
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     * @ORM\Column(type="guid", unique=true)
     */
    private $id;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dispatchDate;

    /**
     * @ORM\Column(type="date", nullable=true)
     * @AppAssert\GreaterThanOrEqualDate("today", groups={"add_survey"})
     */
    private $surveyPeriodStart;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $responseStartDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $submissionDate;

    /**
     * @ORM\Column(type="string", length=20, nullable=false)
     */
    private $state;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getDispatchDate(): ?DateTimeInterface
    {
        return $this->dispatchDate;
    }

    public function setDispatchDate(?DateTimeInterface $dispatchDate): self
    {
        $this->dispatchDate = $dispatchDate;

        return $this;
    }

    public function getSurveyPeriodStart(): ?DateTimeInterface
    {
        return $this->surveyPeriodStart;
    }

    public function setSurveyPeriodStart(?DateTimeInterface $surveyPeriodStart): self
    {
        $this->surveyPeriodStart = $surveyPeriodStart;

        return $this;
    }

    public function getSurveyPeriodStartModifiedBy($modifier)
    {
        if (is_null($this->surveyPeriodStart)) return null;
        return (clone $this->surveyPeriodStart)->modify($modifier);
    }

    public function getResponseStartDate(): ?DateTimeInterface
    {
        return $this->responseStartDate;
    }

    public function setResponseStartDate(?DateTimeInterface $responseStartDate): self
    {
        $this->responseStartDate = $responseStartDate;

        return $this;
    }

    public function getSubmissionDate(): ?DateTimeInterface
    {
        return $this->submissionDate;
    }

    public function setSubmissionDate(?DateTimeInterface $submissionDate): self
    {
        $this->submissionDate = $submissionDate;

        return $this;
    }

    public function getState()
    {
        return $this->state;
    }

    public function setState($state): self
    {
        $this->state = $state;
        return $this;
    }
}
