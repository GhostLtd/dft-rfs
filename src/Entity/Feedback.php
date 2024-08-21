<?php

namespace App\Entity;

use App\Repository\FeedbackRepository;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FeedbackRepository::class)]
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

    #[Assert\NotNull(message: 'survey-feedback.experience-rating.not-null')]
    #[ORM\Column(type: Types::STRING, length: 50)]
    private ?string $experienceRating = null;

    #[Assert\NotNull(message: 'survey-feedback.has-completed-paper.not-null')]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $hasCompletedPaperSurvey = null;

    #[Assert\Expression('!this.getHasCompletedPaperSurvey() || !is_empty(value)', message: 'survey-feedback.comparison-rating.not-null')]
    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $comparisonRating = null;

    #[Assert\Expression('!this.getHasCompletedPaperSurvey() || !is_empty(value)', message: 'survey-feedback.time-to-complete.not-null')]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $timeToComplete = null;

    #[Assert\NotNull(message: 'survey-feedback.had-issues.not-null')]
    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $hadIssues = null;

    #[Assert\Expression("this.getHadIssues() != 'yes-unsolved' || !is_empty(value)", message: 'survey-feedback.time-to-complete.not-null')]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $issueDetails = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comments = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTime $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $exportedAt = null;

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
