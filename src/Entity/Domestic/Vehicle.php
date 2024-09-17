<?php

namespace App\Entity\Domestic;

use App\Entity\VehicleInterface;
use App\Entity\VehicleTrait;
use App\Repository\Domestic\VehicleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

// N.B. Used by trait

#[ORM\Table('domestic_vehicle')]
#[ORM\Entity(repositoryClass: VehicleRepository::class)]
class Vehicle implements VehicleInterface
{
    use VehicleTrait;

    public const array FUEL_TYPES = [
        'diesel' => 'litres',
        'petrol' => 'litres',
        'gas' => 'kg',
        'biofuel' => 'litres',
        'electric' => 'kW',
        'fuel_cell' => 'kg',
        'other' => null,
    ];

    #[Assert\NotNull(groups: ['vehicle_fuel', 'admin_vehicle_fuel'])]
    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    private ?string $fuelType;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $isFuelTypeHybrid;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    private ?string $fuelQuantity;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $fuelQuantityOther;

    #[ORM\OneToOne(targetEntity: SurveyResponse::class, mappedBy: 'vehicle')]
    private ?SurveyResponse $response = null;


    #[Assert\Callback(groups: ['vehicle_fuel', 'admin_vehicle_fuel'])]
    public function validateFuel(ExecutionContextInterface $context): void
    {
        switch ($type = $this->fuelType) {
            case 'other':
                if (is_null($this->fuelQuantityOther)) {
                    $context->buildViolation("domestic.vehicle.fuel-other-quantity.not-blank")->atPath('fuelQuantity_other')->addViolation();
                }
                break;

            case 'diesel': /** @noinspection PhpMissingBreakStatementInspection **/
            case 'petrol':
                if (is_null($this->isFuelTypeHybrid)) {
                    $context->buildViolation("domestic.vehicle.fuel-is-hybrid.not-blank", ['type' => $type])->atPath("fuelTypeIsHybrid_$type")->addViolation();
                }
                // don't break - diesel and petrol also have the same field as the default options.

            default:
                if (is_null($this->fuelQuantity)) {
                    $context->buildViolation("domestic.vehicle.fuel-quantity.not-blank", ['type' => $type])->atPath("fuelQuantity_$type")->addViolation();
                } else if ($this->fuelQuantity < 0) {
                    $context->buildViolation("common.number.positive-or-zero")->atPath("fuelQuantity_$type")->addViolation();
                }
        }
    }

    public function getFuelQuantity(): ?string
    {
        return $this->fuelQuantity;
    }

    public function setFuelQuantity(?string $fuelQuantity): self
    {
        $this->fuelQuantity = $fuelQuantity;
        return $this;
    }

    public function getFuelType(): ?string
    {
        return $this->fuelType;
    }

    public function setFuelType(?string $fuelType): self
    {
        $this->fuelType = $fuelType;
        return $this;
    }

    public function getIsFuelTypeHybrid(): ?bool
    {
        return $this->isFuelTypeHybrid;
    }

    public function setIsFuelTypeHybrid(?bool $isFuelTypeHybrid): self
    {
        $this->isFuelTypeHybrid = $isFuelTypeHybrid;
        return $this;
    }

    public function getFuelQuantityOther(): ?string
    {
        return $this->fuelQuantityOther;
    }

    public function setFuelQuantityOther(?string $fuelQuantityOther): self
    {
        $this->fuelQuantityOther = $fuelQuantityOther;
        return $this;
    }

    public function getResponse(): ?SurveyResponse
    {
        return $this->response;
    }

    public function setResponse(?SurveyResponse $response): self
    {
        $this->response = $response;

        // set the owning side of the relation if necessary
        if ($response->getVehicle() !== $this) {
            $response->setVehicle($this);
        }

        return $this;
    }
}
