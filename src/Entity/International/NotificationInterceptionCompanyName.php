<?php

namespace App\Entity\International;

use App\Entity\IdTrait;
use App\Entity\NotificationInterceptionCompanyNameInterface;
use App\Entity\NotificationInterceptionInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table('international_notification_interception_company_name')]
#[ORM\Entity]
class NotificationInterceptionCompanyName implements NotificationInterceptionCompanyNameInterface
{
    use IdTrait;

    #[Assert\NotBlank(message: 'Company name must not be empty', groups: ['notification_interception'])]
    #[Assert\Regex("^[@()=\[\]\",<>\\\/]", message: 'Company name must not start with a symbol (@ ( ) = [ ] "" , < > \ /)', match: false, groups: ['notification_interception'])]
    #[Assert\Length(max: 255, maxMessage: 'common.string.max-length', groups: ['notification_interception'])]
    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    private ?string $name = null;

    #[ORM\JoinColumn(nullable: false)]
    #[ORM\ManyToOne(targetEntity: NotificationInterception::class, cascade: ['all'], inversedBy: 'additionalNames')]
    private ?NotificationInterception $notificationInterception = null;

    #[\Override]
    public function getName(): ?string
    {
        return $this->name;
    }

    #[\Override]
    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    #[\Override]
    public function getNotificationInterception(): ?NotificationInterception
    {
        return $this->notificationInterception;
    }

    #[\Override]
    public function setNotificationInterception(?NotificationInterception $notificationInterception): self
    {
        $this->notificationInterception = $notificationInterception;
        return $this;
    }
}
