<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable()
 */
class Volume implements ValueUnitInterface
{
    const UNITS_GALLONS = 'gallons';
    const UNITS_LITRES = 'litres';

    const UNITS_TRANSLATION_PREFIX = 'units.volume.';

    const UNITS = [
        self::UNITS_TRANSLATION_PREFIX . self::UNITS_LITRES => self::UNITS_LITRES,
        self::UNITS_TRANSLATION_PREFIX . self::UNITS_GALLONS => self::UNITS_GALLONS,
    ];

    /**
     * @ORM\Column(type="decimal", precision=8, scale=2, nullable=true)
     */
    private $value;

    /**
     * @ORM\Column(type="string", length=8, nullable=true)
     */
    private $units;

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function getUnits(): ?string
    {
        return $this->units;
    }

    public function setUnits(?string $units): self
    {
        $this->units = $units;
        return $this;
    }
}
