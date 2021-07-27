<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable()
 */
class Volume implements ValueUnitInterface
{
    const UNIT_GALLONS = 'gallons';
    const UNIT_LITRES = 'litres';

    const UNIT_TRANSLATION_PREFIX = 'unit.volume.';

    const UNIT_CHOICES = [
        self::UNIT_TRANSLATION_PREFIX . self::UNIT_LITRES => self::UNIT_LITRES,
        self::UNIT_TRANSLATION_PREFIX . self::UNIT_GALLONS => self::UNIT_GALLONS,
    ];

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, nullable=true)
     */
    private $value;

    /**
     * @ORM\Column(type="string", length=8, nullable=true)
     */
    private $unit;

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
