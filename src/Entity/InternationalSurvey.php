<?php

namespace App\Entity;

use App\Repository\InternationalSurveyRepository;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=InternationalSurveyRepository::class)
 */
class InternationalSurvey
{
    use SurveyTrait;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $preSurveyDispatchDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $PreSurveyDueDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $preSurveyResponseStartDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $preSurveySubmissionDate;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $referenceNumber;

    public function getPreSurveyDispatchDate(): ?DateTimeInterface
    {
        return $this->preSurveyDispatchDate;
    }

    public function setPreSurveyDispatchDate(?DateTimeInterface $preSurveyDispatchDate): self
    {
        $this->preSurveyDispatchDate = $preSurveyDispatchDate;

        return $this;
    }

    public function getPreSurveyDueDate(): ?DateTimeInterface
    {
        return $this->PreSurveyDueDate;
    }

    public function setPreSurveyDueDate(?DateTimeInterface $PreSurveyDueDate): self
    {
        $this->PreSurveyDueDate = $PreSurveyDueDate;

        return $this;
    }

    public function getPreSurveyResponseStartDate(): ?DateTimeInterface
    {
        return $this->preSurveyResponseStartDate;
    }

    public function setPreSurveyResponseStartDate(?DateTimeInterface $preSurveyResponseStartDate): self
    {
        $this->preSurveyResponseStartDate = $preSurveyResponseStartDate;

        return $this;
    }

    public function getPreSurveySubmissionDate(): ?DateTimeInterface
    {
        return $this->preSurveySubmissionDate;
    }

    public function setPreSurveySubmissionDate(?DateTimeInterface $preSurveySubmissionDate): self
    {
        $this->preSurveySubmissionDate = $preSurveySubmissionDate;

        return $this;
    }

    public function getReferenceNumber(): ?string
    {
        return $this->referenceNumber;
    }

    public function setReferenceNumber(string $referenceNumber): self
    {
        $this->referenceNumber = $referenceNumber;

        return $this;
    }
}
