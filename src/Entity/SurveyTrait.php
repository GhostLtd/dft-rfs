<?php

namespace App\Entity;

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
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $startDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dueDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $responseStartDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $submissionDate;

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

    public function getStartDate(): ?DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getStartDateModifiedBy($modifier)
    {
        if (is_null($this->startDate)) return null;
        return (clone $this->startDate)->modify($modifier);
    }

    public function getDueDate(): ?DateTimeInterface
    {
        return $this->dueDate;
    }

    public function setDueDate(?DateTimeInterface $dueDate): self
    {
        $this->dueDate = $dueDate;

        return $this;
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
}
