<?php

namespace App\Entity;

/**
 * A survey that NotificationSender can text: supplies the telephone number as entered. NotificationSender
 * normalises it (UkMobileNumber) and sends no SMS when it isn't a UK mobile.
 */
interface SmsRecipientInterface
{
    public function getSmsRecipientTelephone(): ?string;
}
