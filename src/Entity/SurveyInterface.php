<?php

namespace App\Entity;

use DateTime;

// This should be kept in sync with SurveyTrait
interface SurveyInterface
{
    public function getId(): ?string;
    public function getInvitationSentDate(): ?DateTime;
    public function setInvitationSentDate(?DateTime $invitationSentDate): self;
    public function getLatestManualReminderSentDate(): ?DateTime;
    public function setLatestManualReminderSentDate(?DateTime $latestManualReminderSentDate): self;
    public function getResponseStartDate(): ?DateTime;
    public function setResponseStartDate(?DateTime $responseStartDate): self;
    public function getSubmissionDate(): ?DateTime;
    public function setSubmissionDate(?DateTime $submissionDate): self;
    public function getState(): ?string;
    public function setState(?string $state): self;
    public function getInvitationEmails(): ?string;
    public function setInvitationEmails(?string $invitationEmails): self;
    public function getInvitationAddress(): ?LongAddress;
    public function setInvitationAddress(?LongAddress $invitationAddress): self;
    public function getFirstReminderSentDate(): ?DateTime;
    public function setFirstReminderSentDate(?DateTime $firstReminderSentDate): self;
    public function getSecondReminderSentDate(): ?DateTime;
    public function setSecondReminderSentDate(?DateTime $secondReminderSentDate): self;
    public function hasInvitationAddress(): bool;
    public function getFeedback(): ?Feedback;
    public function setFeedback(?Feedback $feedback): self;
    public function getArrayOfInvitationEmails(): array;
    public function hasValidInvitationEmails(): bool;

    // These two methods are not in the trait as implementation differs per Survey
    public function getPasscodeUser(): ?PasscodeUser;
    public function setPasscodeUser(?PasscodeUser $passcodeUser): self;
}
