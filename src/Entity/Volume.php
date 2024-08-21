<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Ghost\GovUkFrontendBundle\Model\ValueUnitInterface;

#[ORM\Embeddable]
class Volume implements ValueUnitInterface
{
    public const UNIT_GALLONS = 'gallons';
    public const UNIT_LITRES = 'litres';

    public const UNIT_TRANSLATION_PREFIX = 'unit.volume.';

    public const UNIT_CHOICES = [
        self::UNIT_TRANSLATION_PREFIX . self::UNIT_LITRES => self::UNIT_LITRES,
        self::UNIT_TRANSLATION_PREFIX . self::UNIT_GALLONS => self::UNIT_GALLONS,
    ];

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    private ?string $value = null;

    #[ORM\Column(type: Types::STRING, length: 8, nullable: true)]
    private ?string $unit = null;

    #[\Override]
    public function getValue(): ?string
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
