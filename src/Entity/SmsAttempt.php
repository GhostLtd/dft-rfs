<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Notify attempts started for one queued SMS (keyed by its notification row), and the financial year and
 * Europe/London day that paid for its first attempt at queue time (`reserved_on` is null for rows written before
 * it existed). Written only by SmsAllowance (DBAL).
 */
#[ORM\Entity(readOnly: true)]
#[ORM\Table('sms_attempt')]
class SmsAttempt
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36, options: ['fixed' => true])]
    private string $notificationId;

    #[ORM\Column(type: Types::SMALLINT)]
    private int $financialYear;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $attempts = 0;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $reservedOn = null;

    public function getNotificationId(): string
    {
        return $this->notificationId;
    }

    public function getFinancialYear(): int
    {
        return $this->financialYear;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function getReservedOn(): ?\DateTimeImmutable
    {
        return $this->reservedOn;
    }
}
