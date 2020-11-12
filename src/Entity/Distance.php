<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable()
 */
class Distance implements ValueUnitInterface
{
    const UNITS_KILOMETERS = 'kilometers';
    const UNITS_MILES = 'miles';

    const UNITS_TRANSLATION_PREFIX = 'distance.units.';

    const UNITS = [
        self::UNITS_TRANSLATION_PREFIX . self::UNITS_MILES => self::UNITS_MILES,
        self::UNITS_TRANSLATION_PREFIX . self::UNITS_KILOMETERS => self::UNITS_KILOMETERS,
    ];

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $value;

    /**
     * @ORM\Column(type="string", length=12, nullable=true)
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
