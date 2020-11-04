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
     * @ORM\Column(type="integer")
     */
    private $grossWeight;

    /**
     * @ORM\Column(type="integer")
     */
    private $carryingCapacity;

    /**
     * @ORM\Column(type="boolean")
     */
    private $forHireAndReward;

    /**
     * @ORM\Column(type="integer")
     */
    private $AxleConfiguration;

    /**
     * @ORM\Column(type="string", length=24, nullable=true)
     */
    private $TrailerType;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRegistrationMark(): ?string
    {
        return $this->registrationMark;
    }

    public function setRegistrationMark(string $registrationMark): self
    {
        $this->registrationMark = $registrationMark;

        return $this;
    }

    public function getGrossWeight(): ?int
    {
        return $this->grossWeight;
    }

    public function setGrossWeight(int $grossWeight): self
    {
        $this->grossWeight = $grossWeight;

        return $this;
    }

    public function getCarryingCapacity(): ?int
    {
        return $this->carryingCapacity;
    }

    public function setCarryingCapacity(int $carryingCapacity): self
    {
        $this->carryingCapacity = $carryingCapacity;

        return $this;
    }

    public function getForHireAndReward(): ?bool
    {
        return $this->forHireAndReward;
    }

    public function setForHireAndReward(bool $forHireAndReward): self
    {
        $this->forHireAndReward = $forHireAndReward;

        return $this;
    }

    public function getAxleConfiguration(): ?int
    {
        return $this->AxleConfiguration;
    }

    public function setAxleConfiguration(int $AxleConfiguration): self
    {
        $this->AxleConfiguration = $AxleConfiguration;

        return $this;
    }

    public function getTrailerType(): ?string
    {
        return $this->TrailerType;
    }

    public function setTrailerType(?string $TrailerType): self
    {
        $this->TrailerType = $TrailerType;

        return $this;
    }
}
