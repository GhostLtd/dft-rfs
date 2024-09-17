<?php

namespace App\Entity\LCNI;

interface NotificationInterceptionInterface
{
    public function getId();
    public function getEmails(): ?string;
    public function setEmails(?string $emails): self;
    public function getPrimaryName(): ?string;
}
