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
     * @Assert\NotBlank(groups={"vehicle_weight"}, message="common.vehicle.gross-weight.not-blank")
     */
    private $grossWeight;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\NotBlank(groups={"vehicle_weight"}, message="common.vehicle.carrying-capacity.not-blank")
     */
    private $carryingCapacity;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\NotBlank(groups={"vehicle_operation_type"}, message="common.vehicle.operation-type.not-blank")
     */
    private $operationType;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\NotBlank(groups={"vehicle_trailer_configuration"}, message="common.vehicle.trailer-configuration.not-blank")
     */
    private $trailerConfiguration;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\NotBlank(groups={"vehicle_axle_configuration"}, message="common.vehicle.axle-configuration.not-blank")
     */
    private $axleConfiguration;

    /**
     * @ORM\Column(type="string", length=24, nullable=true)
     * @Assert\NotBlank(groups={"vehicle_body_type"}, message="common.vehicle.body-type.not-blank")
     */
    private $bodyType;

    /**
     * @Assert\Callback(groups={"vehicle_weight"})
     */
    public function validateWeight(ExecutionContextInterface $context) {
        if ($this->carryingCapacity >= $this->grossWeight) {
            $context
                ->buildViolation('common.vehicle.carrying-capacity.not-more-than-gross-weight')
                ->atPath('carryingCapacity')
                ->addViolation();
        }
    }

//    /**
//     * @Assert\Callback(groups={"vehicle_trailer"})
//     */
//    public function validateTrailerConfiguration(ExecutionContextInterface $context) {
//        $trailerConfiguration = $this->getTrailerConfiguration();
//
//        if (!$trailerConfiguration) {
//            // Symfony's built-in validators will stop us from choosing an option not on the list, so we only
//            // need to concern ourselves with the case where nothing is selected.
//
//            // N.B. We can't just attach a NotBlank to the $axleConfiguration above, as the error won't be attached
//            //      correctly to the form field since its name differs
//            $context
//                ->buildViolation('common.vehicle.trailer-configuration.not-blank')
//                ->atPath('trailerConfiguration')
//                ->addViolation();
//        }
//    }
//
//    /**
//     * @Assert\Callback(groups={"vehicle_axle_configuration"})
//     */
//    public function validateAxleConfiguration(ExecutionContextInterface $context) {
//        $axleConfiguration = $this->getAxleConfiguration();
//
//        $axleChoice = $axleConfiguration ? $axleConfiguration % 100 : null;
//
//        dump([$axleConfiguration, $axleChoice]);
//
//        if (!$axleConfiguration || !$axleChoice) {
//            $context
//                ->buildViolation('common.vehicle.axle-configuration.not-blank')
//                ->atPath('axleConfiguration')
//                ->addViolation();
//        }
//    }

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
