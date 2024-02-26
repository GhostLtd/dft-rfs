<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

trait SimpleVehicleTrait
{
    #[Assert\NotBlank(message: 'common.vehicle.gross-weight.not-blank', groups: ['vehicle_weight', 'admin_vehicle'])]
    #[Assert\Positive(message: 'common.number.positive', groups: ['vehicle_weight', 'admin_vehicle'])]
    #[Assert\Range(maxMessage: 'common.number.max', max: 2000000000, groups: ['vehicle_weight', 'admin_vehicle'])]
    #[Assert\Range(minMessage: 'common.vehicle.gross-weight.minimum', min: 3500, groups: ['vehicle_weight', 'admin_vehicle'])]
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $grossWeight = null;

    #[Assert\NotBlank(message: 'common.vehicle.carrying-capacity.not-blank', groups: ['vehicle_weight', 'admin_vehicle'])]
    #[Assert\Positive(message: 'common.number.positive', groups: ['vehicle_weight', 'admin_vehicle'])]
    #[Assert\Range(maxMessage: 'common.number.max', max: 2000000000, groups: ['vehicle_weight', 'admin_vehicle'])]
    #[Assert\Range(minMessage: 'common.vehicle.carrying-capacity.minimum', min: 1000, groups: ['vehicle_weight', 'admin_vehicle'])]
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $carryingCapacity = null;

    #[Assert\NotBlank(message: 'common.vehicle.trailer-configuration.not-blank', groups: ['vehicle_trailer_configuration'])]
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $trailerConfiguration = null;

    #[Assert\NotBlank(message: 'common.vehicle.axle-configuration.not-blank', groups: ['vehicle_axle_configuration', 'admin_vehicle'])]
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $axleConfiguration = null;

    #[Assert\NotBlank(message: 'common.vehicle.body-type.not-blank', groups: ['vehicle_body_type', 'admin_vehicle'])]
    #[ORM\Column(type: Types::STRING, length: 24, nullable: true)]
    private ?string $bodyType = null;

    #[Assert\Callback(groups: ['vehicle_weight', 'admin_vehicle'])]
    public function validateWeight(ExecutionContextInterface $context): void
    {
        if ($this->carryingCapacity !== null && $this->carryingCapacity >= $this->grossWeight) {
            $context
                ->buildViolation('common.vehicle.carrying-capacity.not-more-than-gross-weight')
                ->atPath('carryingCapacity')
                ->addViolation();
        }
    }

    public function getGrossWeight(): ?int
    {
        return $this->grossWeight;
    }

    public function setGrossWeight(?int $grossWeight): self
    {
        $this->grossWeight = $grossWeight;
        return $this;
    }

    public function getCarryingCapacity(): ?int
    {
        return $this->carryingCapacity;
    }

    public function setCarryingCapacity(?int $carryingCapacity): self
    {
        $this->carryingCapacity = $carryingCapacity;
        return $this;
    }

    public function getTrailerConfiguration(): ?int
    {
        return $this->trailerConfiguration;
    }

    public function setTrailerConfiguration(?int $trailerConfiguration): self
    {
        $this->trailerConfiguration = $trailerConfiguration;
        return $this;
    }

    public function getAxleConfiguration(): ?int
    {
        return $this->axleConfiguration;
    }

    public function setAxleConfiguration(?int $axleConfiguration): self
    {
        if ($axleConfiguration === null) {
            $this->trailerConfiguration = null;
        } else {
            $this->trailerConfiguration = intval($axleConfiguration / 100) * 100;
        }

        $this->axleConfiguration = $axleConfiguration;
        return $this;
    }

    public function getBodyType(): ?string
    {
        return $this->bodyType;
    }

    public function setBodyType(?string $bodyType): self
    {
        $this->bodyType = $bodyType;
        return $this;
    }
}
