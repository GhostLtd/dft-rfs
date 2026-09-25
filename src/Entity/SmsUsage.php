<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/** SMS messages charged against the Notify allowance, per financial year. Written only by SmsAllowance (DBAL). */
#[ORM\Entity(readOnly: true)]
#[ORM\Table('sms_usage')]
class SmsUsage
{
    #[ORM\Id]
    #[ORM\Column(type: Types::SMALLINT)]
    private int $financialYear;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $sent = 0;

    public function getFinancialYear(): int
    {
        return $this->financialYear;
    }

    public function getSent(): int
    {
        return $this->sent;
    }
}
