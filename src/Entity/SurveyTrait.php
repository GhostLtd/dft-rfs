<?php

namespace App\Entity;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

trait SurveyTrait {
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
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

    /**
     * @ORM\Column(type="string", length=16)
     */
    private $passcode;

    public function getId(): ?int
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

    public function getPasscode(): ?string
    {
        return $this->passcode;
    }

    public function setPasscode(string $passcode): self
    {
        $this->passcode = $passcode;

        return $this;
    }
}
