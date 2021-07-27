<?php

namespace App\Entity;

use App\Utility\RegistrationMarkHelper;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use App\Workflow\FormWizardManager;

trait VehicleTrait
{
    use SimpleVehicleTrait;
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     * @ORM\Column(type="guid", unique=true)
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=10)
     * @Assert\NotBlank(groups={"vehicle_registration", "admin_vehicle"}, message="common.vehicle.vehicle-registration.not-blank")
     * @Groups({FormWizardManager::NOTIFICATION_BANNER_NORMALIZER_GROUP})
     */
    private $registrationMark;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\NotBlank(groups={"vehicle_operation_type", "admin_vehicle", "admin_business"}, message="common.vehicle.operation-type.not-blank")
     */
    private $operationType;

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

    public function getOperationType(): ?string
    {
        return $this->operationType;
    }

    public function setOperationType(?string $operationType): self
    {
        $this->operationType = $operationType;

        return $this;
    }
}
