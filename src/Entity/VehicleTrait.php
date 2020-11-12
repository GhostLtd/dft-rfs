<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

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
        $this->registrationMark = $registrationMark;

        return $this;
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
