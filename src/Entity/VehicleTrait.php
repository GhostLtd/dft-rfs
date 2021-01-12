<?php

namespace App\Entity;

use App\Utility\RegistrationMarkHelper;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

trait VehicleTrait
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     * @ORM\Column(type="guid", unique=true)
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=10)
     * @Assert\NotBlank(groups={"vehicle_registration", "admin_vehicle"}, message="common.vehicle.vehicle-registration.not-blank")
     */
    private $registrationMark;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\NotBlank(groups={"vehicle_weight", "admin_vehicle"}, message="common.vehicle.gross-weight.not-blank")
     * @Assert\Positive(message="common.number.positive", groups={"vehicle_weight", "admin_vehicle"})
     * @Assert\Range(groups={"vehicle_weight", "admin_vehicle"}, max=2000000000, maxMessage="common.number.max")
     */
    private $grossWeight;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\NotBlank(groups={"vehicle_weight", "admin_vehicle"}, message="common.vehicle.carrying-capacity.not-blank")
     * @Assert\Positive(message="common.number.positive", groups={"vehicle_weight", "admin_vehicle"})
     * @Assert\Range(groups={"vehicle_weight", "admin_vehicle"}, max=2000000000, maxMessage="common.number.max")
     */
    private $carryingCapacity;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\NotBlank(groups={"vehicle_operation_type", "admin_vehicle"}, message="common.vehicle.operation-type.not-blank")
     */
    private $operationType;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\NotBlank(groups={"vehicle_trailer_configuration", "admin_vehicle"}, message="common.vehicle.trailer-configuration.not-blank")
     */
    private $trailerConfiguration;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\NotBlank(groups={"vehicle_axle_configuration", "admin_vehicle"}, message="common.vehicle.axle-configuration.not-blank")
     */
    private $axleConfiguration;

    /**
     * @ORM\Column(type="string", length=24, nullable=true)
     * @Assert\NotBlank(groups={"vehicle_body_type", "admin_vehicle"}, message="common.vehicle.body-type.not-blank")
     */
    private $bodyType;

    /**
     * @Assert\Callback(groups={"vehicle_weight", "admin_vehicle"})
     */
    public function validateWeight(ExecutionContextInterface $context) {
        if ($this->carryingCapacity !== null && $this->carryingCapacity >= $this->grossWeight) {
            $context
                ->buildViolation('common.vehicle.carrying-capacity.not-more-than-gross-weight')
                ->atPath('carryingCapacity')
                ->addViolation();
        }
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getRegistrationMark(): ?string
    {
        return $this->registrationMark;
    }

    public function setRegistrationMark(?string $registrationMark): self
    {
        $helper = new RegistrationMarkHelper($registrationMark);
        $this->registrationMark = $helper->getRegistrationMark();

        return $this;
    }

    public function getFormattedRegistrationMark(): ?string
    {
        return (new RegistrationMarkHelper($this->registrationMark))->getFormattedRegistrationMark();
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

    public function getOperationType(): ?string
    {
        return $this->operationType;
    }

    public function setOperationType(?string $operationType): self
    {
        $this->operationType = $operationType;

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
