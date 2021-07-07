<?php

namespace App\Entity;

use App\Entity\LongAddress; // PHPstorm indicates this isn't needed, but it is
use App\Form\Validator as AppAssert;
use DateTime;
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
     * @ORM\Column(type="json", nullable=true)
     */
    private ?array $notifyApiResponses = [];

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
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Email(groups={"add_survey", "import_survey"})
     * @Assert\Length(max=255, maxMessage="common.string.max-length", groups={"add_survey", "import_survey"})
     */
    private $invitationEmail;

    /**
     * @ORM\Embedded(class=LongAddress::class)
     * @AppAssert\ValidAddress(groups={"add_survey", "import_survey"}, validatePostcode=true, allowBlank=true)
     *
     * @var LongAddress|null
     */
    private $invitationAddress;

    /**
     * @Assert\Callback(groups={"add_survey.not-enabled"})
     */
    public function validInvitationDetails(ExecutionContextInterface $context) {
        if (!$this->invitationEmail && (!$this->invitationAddress || !$this->invitationAddress->isFilled())) {
            $context
                ->buildViolation('international.add.invitation-email-or-address')
                ->atPath('invitationEmail')
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

    public function getState(): string
    {
        return $this->state;
    }

    public function setState($state): self
    {
        $this->state = $state;
        return $this;
    }

    public function getInvitationEmail(): ?string
    {
        return $this->invitationEmail;
    }

    public function setInvitationEmail(?string $invitationEmail): self
    {
        $this->invitationEmail = $invitationEmail;

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

    public function getNotifyApiResponses(): ?array
    {
        return $this->notifyApiResponses;
    }

    public function getNotifyApiResponse(string $eventName, string $notificationClass): ?array
    {
        return $this->notifyApiResponses["{$eventName}::{$notificationClass}"] ?? null;
    }

    public function setNotifyApiResponse(string $eventName, string $notificationClass, array $notifyApiResponse): self
    {
        if (!is_array($this->notifyApiResponses)) $this->notifyApiResponses = [];
        $this->notifyApiResponses["{$eventName}::{$notificationClass}"] = $notifyApiResponse;
        return $this;
    }

    public function hasInvitationAddress(): bool
    {
        return $this->invitationAddress instanceof LongAddress && $this->invitationAddress->isFilled();
    }

    protected function getNotifyLetterStatus(string $constSuffix): ?string
    {
//        $surveyType = preg_split('/', get_class($this));
//        $surveyType = strtoupper($surveyType[count($surveyType) - 2]);
//        $response = $this->getNotifyApiResponse("LETTER_{$surveyType}_SURVEY_INVITE");
//        dump($response); exit;

        // check if it has 'code' or 'errors' (indicating an error)
        // check if the error was recoverable
        return 'something';
    }
}