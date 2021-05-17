<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable()
 */
class Distance implements ValueUnitInterface
{
    const UNIT_KILOMETERS = 'kilometers';
    const UNIT_MILES = 'miles';

    const UNIT_CONVERSION_FACTOR = 1.609344;

    const UNIT_TRANSLATION_PREFIX = 'unit.distance.';

    const UNIT_CHOICES = [
        self::UNIT_TRANSLATION_PREFIX . self::UNIT_MILES => self::UNIT_MILES,
        self::UNIT_TRANSLATION_PREFIX . self::UNIT_KILOMETERS => self::UNIT_KILOMETERS,
    ];

    /**
     * @ORM\Column(type="decimal", precision=10, scale=1, nullable=true)
     */
    private $value;

    /**
     * @ORM\Column(type="string", length=12, nullable=true)
     */
    private $unit;

    public function __toString()
    {
        return "{$this->value} {$this->unit}";
    }

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
        switch ($unit) {
            case self::UNIT_KILOMETERS :
                return $this->value * self::UNIT_CONVERSION_FACTOR;
            case self::UNIT_MILES :
                return $this->value / self::UNIT_CONVERSION_FACTOR;
            default:
                throw new \InvalidArgumentException('Unexpected unit type');
        }
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
