<?php

namespace App\Entity;

interface NotificationInterceptionCompanyNameInterface
{
    public function getName(): ?string;
    public function setName(?string $name): self;
    public function getNotificationInterception(): ?NotificationInterceptionInterface;
    public function setNotificationInterception(?NotificationInterceptionInterface $notificationInterception): self;
}
