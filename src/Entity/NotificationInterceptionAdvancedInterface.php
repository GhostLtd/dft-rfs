<?php

namespace App\Entity;

use Doctrine\Common\Collections\Collection;
use App\Form\Validator as AppAssert;

#[AppAssert\UniqueNotificationInterceptionName(groups: ['notification_interception'])]
interface NotificationInterceptionAdvancedInterface extends NotificationInterceptionInterface
{
    public function getAdditionalNames(): ?Collection;
    public function addAdditionalName(NotificationInterceptionCompanyNameInterface $companyName): self;
    public function removeAdditionalName(NotificationInterceptionCompanyNameInterface $companyName): self;
}
