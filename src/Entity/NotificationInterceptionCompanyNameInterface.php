<?php

namespace App\Entity;

use App\Entity\International\NotificationInterception;

interface NotificationInterceptionCompanyNameInterface
{
    public function getName(): ?string;
    public function setName(?string $name): self;
    public function getNotificationInterception(): ?NotificationInterception;
    public function setNotificationInterception(?NotificationInterception $notificationInterception): self;
}
