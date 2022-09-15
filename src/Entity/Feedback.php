<?php

namespace App\Entity;

use App\Repository\FeedbackRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=FeedbackRepository::class)
 */
class Feedback
{
    use IdTrait;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function __construct()
    {
        $this->createdAt = new DateTime();
    }

    /**
     * @ORM\Column(type="string", length=50)
     * @Assert\NotNull(message="survey-feedback.experience-rating.not-null")
     */
    private ?string $experienceRating = null;

    /**
     * @ORM\Column(type="boolean")
     * @Assert\NotNull(message="survey-feedback.has-completed-paper.not-null")
     */
    private ?bool $hasCompletedPaperSurvey = null;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Assert\Expression("!this.getHasCompletedPaperSurvey() || !is_empty(value)", message="survey-feedback.comparison-rating.not-null")
     */
    private ?string $comparisonRating = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Expression("!this.getHasCompletedPaperSurvey() || !is_empty(value)", message="survey-feedback.time-to-complete.not-null")
     */
    private ?string $timeToComplete = null;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotNull(message="survey-feedback.had-issues.not-null")
     */
    private ?string $hadIssues = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Assert\Expression("this.getHadIssues() != 'yes-unsolved' || !is_empty(value)", message="survey-feedback.time-to-complete.not-null")
     */
    private ?string $issueDetails = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $comments = null;

    /**
     * @ORM\Column(type="datetime")
     */
    private \DateTime $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTime $exportedAt;

    public function getExperienceRating(): ?string
    {
        return $this->experienceRating;
    }

    public function setExperienceRating(?string $experienceRating): self
    {
        $this->experienceRating = $experienceRating;

        return $this;
    }

    public function getHasCompletedPaperSurvey(): ?bool
    {
        return $this->hasCompletedPaperSurvey;
    }

    public function setHasCompletedPaperSurvey(?bool $hasCompletedPaperSurvey): self
    {
        $this->hasCompletedPaperSurvey = $hasCompletedPaperSurvey;

        return $this;
    }

    public function getComparisonRating(): ?string
    {
        return $this->comparisonRating;
    }

    public function setComparisonRating(?string $comparisonRating): self
    {
        $this->comparisonRating = $comparisonRating;

        return $this;
    }

    public function getTimeToComplete(): ?string
    {
        return $this->timeToComplete;
    }

    public function setTimeToComplete(?string $timeToComplete): self
    {
        $this->timeToComplete = $timeToComplete;

        return $this;
    }

    public function getHadIssues(): ?string
    {
        return $this->hadIssues;
    }

    public function setHadIssues(?string $hadIssues): self
    {
        $this->hadIssues = $hadIssues;

        return $this;
    }

    public function getIssueDetails(): ?string
    {
        return $this->issueDetails;
    }

    public function setIssueDetails(?string $issueDetails): self
    {
        $this->issueDetails = $issueDetails;

        return $this;
    }

    public function getComments(): ?string
    {
        return $this->comments;
    }

    public function setComments(?string $comments): self
    {
        $this->comments = $comments;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getExportedAt(): ?\DateTime
    {
        return $this->exportedAt;
    }

    public function setExportedAt(?\DateTime $exportedAt): self
    {
        $this->exportedAt = $exportedAt;

        return $this;
    }
}
