<?php

namespace App\Entity;

use App\Utility\RegistrationMarkHelper;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use App\Workflow\FormWizardManager;

trait VehicleTrait
{
    use SimpleVehicleTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: Types::STRING, length: 36, unique: true, options: ['fixed' => true])]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?string $id = null;

    #[Assert\NotBlank(message: 'common.vehicle.vehicle-registration.not-blank', groups: ['vehicle_registration', 'admin_vehicle'])]
    #[Groups([FormWizardManager::NOTIFICATION_BANNER_NORMALIZER_GROUP])]
    #[ORM\Column(type: Types::STRING, length: 10)]
    private ?string $registrationMark = null;

    #[Assert\NotBlank(message: 'common.vehicle.operation-type.not-blank', groups: ['vehicle_operation_type', 'admin_vehicle', 'admin_business'])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $operationType = null;

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
