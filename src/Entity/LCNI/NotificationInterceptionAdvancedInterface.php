<?php

namespace App\Entity\LCNI;

use App\Form\Validator as AppAssert;
use Doctrine\Common\Collections\Collection;

#[AppAssert\UniqueNotificationInterceptionName(groups: ['notification_interception'])]
interface NotificationInterceptionAdvancedInterface extends NotificationInterceptionInterface
{
    public function getAdditionalNames(): ?Collection;
    public function addAdditionalName(NotificationInterceptionCompanyNameInterface $companyName): self;
    public function removeAdditionalName(NotificationInterceptionCompanyNameInterface $companyName): self;
}
