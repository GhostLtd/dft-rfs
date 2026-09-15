<?php

namespace App\Entity;

interface SurveyManualReminderInterface extends SurveyNotesInterface, SurveyNotificationsInterface
{
    public function getId(): ?string;

    public function getArrayOfInvitationEmails(): array;
    public function getResponseContactEmail(): ?string;
    public function getState(): ?string;
    public function setState(string $state): self;
}
