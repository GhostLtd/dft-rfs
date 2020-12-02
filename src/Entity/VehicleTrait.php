<?php

namespace App\Entity;

use App\Utility\RegistrationMarkHelper;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

trait VehicleTrait
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=10)
     * @Assert\NotBlank(groups={"vehicle_registration"}, message="common.vehicle.vehicle-registration.not-blank")
     */
    private $registrationMark;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $grossWeight;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $carryingCapacity;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $operationType;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $axleConfiguration;

    /**
     * @ORM\Column(type="string", length=24, nullable=true)
     */
    private $bodyType;

    public function getId(): ?int
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
        if (is_numeric($this->axleConfiguration))
        {
            // get the root category
            return floor($this->axleConfiguration / 100) * 100;
        }
        return $this->axleConfiguration;
    }

    public function setTrailerConfiguration(?int $trailerConfiguration): self
    {
        if ($trailerConfiguration !== $this->getTrailerConfiguration())
        {
            // we're changing trailer configuration, so axle configuration should be reset.
            $this->axleConfiguration = $trailerConfiguration;
        }
        return $this;
    }

    public function getTrailerGroup()
    {
        return $this->getAxleConfiguration() ? 100 * floor($this->getAxleConfiguration() / 100) : null;
    }

    public function getAxleConfiguration(): ?int
    {
        return $this->axleConfiguration;
    }

    public function setAxleConfiguration(?int $axleConfiguration): self
    {
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
