<?php

namespace App\Entity;

use DateTime;

interface SurveyManualReminderInterface extends SurveyNotesInterface
{
    public function getId(): ?string;
    public function getLatestManualReminderSentDate(): ?DateTime;
    public function setLatestManualReminderSentDate(?DateTime $latestManualReminderSentDate): self;

    public function getArrayOfInvitationEmails(): array;
    public function getResponseContactEmail(): ?string;
    public function getState(): ?string;
    public function setState(string $state): self;
}
