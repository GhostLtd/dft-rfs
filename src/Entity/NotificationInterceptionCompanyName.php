<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[UniqueEntity(fields: ['name'], message: 'A record with the name {{ value }} already exists', groups: ['notification_interception_names'])]
class NotificationInterceptionCompanyName
{
    use IdTrait;

    #[Assert\NotBlank(message: 'Company name must not be empty', groups: ['notification_interception_names'])]
    #[Assert\Regex("#^[@=\[\],<>\\\/]#", message: 'Company name must not start with a symbol (@ = [ ] "" , < > \ /)', match: false, groups: ['notification_interception_names'])]
    #[Assert\Length(max: 255, maxMessage: 'common.string.max-length', groups: ['notification_interception_names'])]
    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    private ?string $name = null;

    #[ORM\JoinColumn(nullable: false)]
    #[ORM\ManyToOne(targetEntity: NotificationInterception::class, cascade: ['all'], inversedBy: 'names')]
    private ?NotificationInterception $notificationInterception = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getNotificationInterception(): ?NotificationInterception
    {
        return $this->notificationInterception;
    }

    public function setNotificationInterception(?NotificationInterception $notificationInterception): self
    {
        $this->notificationInterception = $notificationInterception;
        return $this;
    }
}
