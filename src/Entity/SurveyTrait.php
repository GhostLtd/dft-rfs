<?php

namespace App\Entity;

use App\Entity\LongAddress; // PHPstorm indicates this isn't needed, but it is
use App\Entity\Feedback; // PHPstorm indicates this isn't needed, but it is
use App\Form\Validator as AppAssert;
use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

trait SurveyTrait
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     * @ORM\Column(type="guid", unique=true)
     */
    private $id;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTime $notifiedDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTime $firstReminderSentDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTime $secondReminderSentDate;

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

    /**
     * @ORM\Column(type="string", length=1024, nullable=true)
     * @AppAssert\CommaSeparatedEmails(groups={"add_survey", "import_survey"}, message="common.email.list-contains-invalid")
     * @Assert\Length(max=1024, maxMessage="common.string.max-length", groups={"add_survey", "import_survey"})
     */
    private $invitationEmails;

    /**
     * @ORM\Embedded(class=LongAddress::class)
     * @AppAssert\ValidAddress(groups={"add_survey", "import_survey"}, validatePostcode=true, allowBlank=true)
     * @Assert\Valid(groups={"add_srvuey", "import_survey", "notify_api"})
     *
     * @var LongAddress|null
     */
    private $invitationAddress;

    /**
     * @ORM\OneToOne(targetEntity=Feedback::class, cascade={"persist", "remove"})
     */
    private ?Feedback $feedback;

    private Collection $apiResponses;

    /**
     * @Assert\Callback(groups={"add_survey.not-enabled"})
     */
    public function validInvitationDetails(ExecutionContextInterface $context) {
        if (!$this->invitationEmails && (!$this->invitationAddress || !$this->invitationAddress->isFilled())) {
            $context
                ->buildViolation('international.add.invitation-email-or-address')
                ->atPath('invitationEmails')
                ->addViolation();
            // need to add the context to both, because email address is disabled (and therefore won't trip the form->isValid check)
            $context
                ->buildViolation('international.add.invitation-email-or-address')
                ->atPath('invitationAddress')
                ->addViolation();
        }
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getNotifiedDate(): ?DateTime
    {
        return $this->notifiedDate;
    }

    public function setNotifiedDate(?DateTime $notifiedDate): self
    {
        $this->notifiedDate = $notifiedDate;

        return $this;
    }

    public function getResponseStartDate(): ?DateTime
    {
        return $this->responseStartDate;
    }

    public function setResponseStartDate(?DateTime $responseStartDate): self
    {
        $this->responseStartDate = $responseStartDate;

        return $this;
    }

    public function getSubmissionDate(): ?DateTime
    {
        return $this->submissionDate;
    }

    public function setSubmissionDate(?DateTime $submissionDate): self
    {
        $this->submissionDate = $submissionDate;

        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState($state): self
    {
        $this->state = $state;
        return $this;
    }

    public function getInvitationEmails(): ?string
    {
        return $this->invitationEmails;
    }

    public function setInvitationEmails(?string $invitationEmails): self
    {
        $this->invitationEmails = $invitationEmails;

        return $this;
    }

    public function getInvitationAddress(): ?LongAddress
    {
        return $this->invitationAddress;
    }

    public function setInvitationAddress(?LongAddress $invitationAddress): self
    {
        $this->invitationAddress = $invitationAddress;

        return $this;
    }

    public function setFirstReminderSentDate(?\DateTime $firstReminderSentDate): self
    {
        $this->firstReminderSentDate = $firstReminderSentDate;
        return $this;
    }

    public function getFirstReminderSentDate(): ?\DateTime
    {
        return $this->firstReminderSentDate;
    }

    public function setSecondReminderSentDate(?\DateTime $secondReminderSentDate): self
    {
        $this->secondReminderSentDate = $secondReminderSentDate;
        return $this;
    }

    public function getSecondReminderSentDate(): ?\DateTime
    {
        return $this->secondReminderSentDate;
    }

    public function hasInvitationAddress(): bool
    {
        return $this->invitationAddress instanceof LongAddress && $this->invitationAddress->isFilled();
    }

    public function getFeedback(): ?Feedback
    {
        return $this->feedback ?? null;
    }

    public function setFeedback(?Feedback $feedback): self
    {
        $this->feedback = $feedback;
        return $this;
    }

    public function getNotifyApiResponses(): Collection
    {
        return $this->apiResponses;
    }

    public function getNotifyApiResponsesMatching(string $eventName, string $notificationClass, bool $sorted = false): array
    {
        $responses = $this->apiResponses
            ->filter(fn(NotifyApiResponse $r) =>
                $r->getEventName() === $eventName &&
                $r->getMessageClass() === $notificationClass
            )
            ->toArray();

        if ($sorted) {
            usort($responses,
                fn(NotifyApiResponse $a, NotifyApiResponse $b) => $b->getTimestamp() <=> $a->getTimestamp()
            );
        }

        return $responses;
    }

    public function addNotifyApiResponse(NotifyApiResponse $apiResponse): self {
        if (!$this->apiResponses->contains($apiResponse)) {
            $this->apiResponses[] = $apiResponse;
        }

        return $this;
    }

    public function removeNotifyApiResponse(NotifyApiResponse $apiResponse): self
    {
        $this->apiResponses->removeElement($apiResponse);
        return $this;
    }
}