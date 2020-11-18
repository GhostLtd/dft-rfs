<?php

namespace App\Entity;

use App\Repository\InternationalPreEnquiryRepository;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=InternationalPreEnquiryRepository::class)
 */
class InternationalPreEnquiry
{
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
    private $dueDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $submissionDate;

    /**
     * @ORM\OneToOne(targetEntity=InternationalPreEnquiryResponse::class, mappedBy="preEnquiry", cascade={"persist"})
     */
    private $response;

    /**
     * @ORM\ManyToOne(targetEntity=InternationalCompany::class, inversedBy="preEnquiries")
     * @ORM\JoinColumn(nullable=false)
     */
    private $company;

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

    public function getDueDate(): ?DateTimeInterface
    {
        return $this->dueDate;
    }

    public function setDueDate(?DateTimeInterface $dueDate): self
    {
        $this->dueDate = $dueDate;

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

    public function getResponse(): ?InternationalPreEnquiryResponse
    {
        return $this->response;
    }

    public function setResponse(InternationalPreEnquiryResponse $response): self
    {
        $this->response = $response;

        // set the owning side of the relation if necessary
        if ($response->getPreEnquiry() !== $this) {
            $response->setPreEnquiry($this);
        }

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
}
