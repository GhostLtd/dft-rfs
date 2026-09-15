<?php

namespace App\Entity;

use App\Entity\Embeddable\LongAddress;
use DateTime;

// This should be kept in sync with SurveyTrait
interface SurveyInterface extends SurveyNotificationsInterface
{
    public function getId(): ?string;
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
    public function hasInvitationAddress(): bool;
    public function getFeedback(): ?Feedback;
    public function setFeedback(?Feedback $feedback): self;
    public function getArrayOfInvitationEmails(): array;
    public function hasValidInvitationEmails(): bool;

    // These two methods are not in the trait as implementation differs per Survey
    public function getPasscodeUser(): ?PasscodeUser;
    public function setPasscodeUser(?PasscodeUser $passcodeUser): self;

    public function getResponseContactEmail(): ?string;

    public function getTransientNotificationInterception(): ?NotificationInterception;
    public function setTransientNotificationInterception(?NotificationInterception $transientNotificationInterception): self;
}
