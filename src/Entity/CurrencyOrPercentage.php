<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Ghost\GovUkFrontendBundle\Model\ValueUnitInterface;

#[ORM\Embeddable]
class CurrencyOrPercentage implements ValueUnitInterface
{
    public const UNIT_CURRENCY = 'currency';
    public const UNIT_PERCENTAGE = 'percentage';

    public const UNIT_TRANSLATION_PREFIX = 'domestic.driver-availability.currency-or-percentage.';

    public const UNIT_CHOICES = [
        self::UNIT_TRANSLATION_PREFIX . self::UNIT_PERCENTAGE => self::UNIT_PERCENTAGE,
        self::UNIT_TRANSLATION_PREFIX . self::UNIT_CURRENCY => self::UNIT_CURRENCY,
    ];

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $value = null;

    #[ORM\Column(type: Types::STRING, length: 12, nullable: true)]
    private ?string $unit = null;

    #[\Override]
    public function getValue(): ?int
    {
        return $this->value;
    }

    #[\Override]
    public function setValue($value): self
    {
        $this->value = $value;
        return $this;
    }

    #[\Override]
    public function getUnit(): ?string
    {
        return $this->unit;
    }

    #[\Override]
    public function setUnit(?string $unit): self
    {
        $this->unit = $unit;
        return $this;
    }

    #[\Override]
    public function getIsBlank(): bool
    {
        return empty($this->value);
    }
}
