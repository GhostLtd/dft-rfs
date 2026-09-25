<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * SMS attempts charged to one mobile number (stored only as an HMAC) on one Europe/London day. Written only by
 * SmsRecipientDailyCap (DBAL); earlier days are pruned daily.
 *
 * Never load it through the ORM: its identifier includes a date, which Doctrine's identity map can't hash.
 */
#[ORM\Entity(readOnly: true)]
#[ORM\Table('sms_recipient_day')]
class SmsRecipientDay
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 64, options: ['fixed' => true])]
    private string $recipientHmac;

    #[ORM\Id]
    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $day;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $sent = 0;

    public function getRecipientHmac(): string
    {
        return $this->recipientHmac;
    }

    public function getDay(): \DateTimeImmutable
    {
        return $this->day;
    }

    public function getSent(): int
    {
        return $this->sent;
    }
}
