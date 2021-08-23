<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable()
 */
class CurrencyOrPercentage implements ValueUnitInterface
{
    const UNIT_CURRENCY = 'currency';
    const UNIT_PERCENTAGE = 'percentage';

    const UNIT_TRANSLATION_PREFIX = 'domestic.driver-availability.currency-or-percentage.';

    const UNIT_CHOICES = [
        self::UNIT_TRANSLATION_PREFIX . self::UNIT_PERCENTAGE => self::UNIT_PERCENTAGE,
        self::UNIT_TRANSLATION_PREFIX . self::UNIT_CURRENCY => self::UNIT_CURRENCY,
    ];

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $value = null;

    /**
     * @ORM\Column(type="string", length=12, nullable=true)
     */
    private ?string $unit = null;

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue($value): self
    {
        $this->value = $value;
        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): self
    {
        $this->unit = $unit;
        return $this;
    }

    public function isBlank(): bool
    {
        return empty($this->value);
    }
}
