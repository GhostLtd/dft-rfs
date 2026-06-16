<?php

namespace App\Entity\Embeddable;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Ghost\GovUkFrontendBundle\Model\ValueUnitInterface;

#[ORM\Embeddable]
class Distance implements ValueUnitInterface, \Stringable
{
    public const string UNIT_KILOMETRES = 'kilometres';
    public const string UNIT_MILES = 'miles';

    public const float UNIT_CONVERSION_FACTOR = 1.609344;

    public const string UNIT_TRANSLATION_PREFIX = 'unit.distance.';

    public const array UNIT_CHOICES = [
        self::UNIT_TRANSLATION_PREFIX . self::UNIT_MILES => self::UNIT_MILES,
        self::UNIT_TRANSLATION_PREFIX . self::UNIT_KILOMETRES => self::UNIT_KILOMETRES,
    ];

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 1, nullable: true)]
    private ?string $value = null;

    #[ORM\Column(type: Types::STRING, length: 12, nullable: true)]
    private ?string $unit = null;

    #[\Override]
    public function __toString(): string
    {
        return "{$this->value} {$this->unit}";
    }

    #[\Override]
    public function getValue(): ?string
    {
        return $this->value;
    }

    public function getValueNormalized($unit)
    {
        if ($this->value === null) {
            return null;
        }

        if ($this->unit === $unit) {
            return $this->value;
        }

        return match ($unit) {
            self::UNIT_KILOMETRES => (float) $this->value * self::UNIT_CONVERSION_FACTOR,
            self::UNIT_MILES => (float) $this->value / self::UNIT_CONVERSION_FACTOR,
            default => throw new \InvalidArgumentException('Unexpected unit type'),
        };
    }

    #[\Override]
    public function setValue($value): static
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
    public function setUnit(?string $unit): static
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
