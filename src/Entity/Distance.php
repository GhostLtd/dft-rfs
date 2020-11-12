<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable()
 */
class Distance
{
    const UNITS_KILOMETERS = 'kilometers';
    const UNITS_MILES = 'miles';

    const UNITS = [
        'distance.units.miles' => self::UNITS_MILES,
        'distance.units.kilometers' => self::UNITS_KILOMETERS,
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
