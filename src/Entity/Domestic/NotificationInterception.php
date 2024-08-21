<?php

namespace App\Entity\Domestic;

use App\Entity\NotificationInterceptionInterface;
use App\Repository\Domestic\NotificationInterceptionRepository;
use App\Form\Validator as AppAssert;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[UniqueEntity('addressLine', message: 'notification.address-line.duplicate', groups: ['notification_interception'])]
#[ORM\Table('domestic_notification_interception')]
#[ORM\Entity(repositoryClass: NotificationInterceptionRepository::class)]
class NotificationInterception implements NotificationInterceptionInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[Assert\NotBlank(message: 'Address line must not be empty', groups: ['notification_interception'])]
    #[Assert\Regex("#^[@()=\[\]\",<>\\\/]#", message: 'Address lines must not start with a symbol (@ ( ) = [ ] "" , < > \ /)', match: false, groups: ['notification_interception'])]
    #[Assert\Length(max: 255, maxMessage: 'common.string.max-length', groups: ['notification_interception'])]
    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    private ?string $addressLine = null;

    #[Assert\NotBlank(groups: ['notification_interception'])]
    #[Assert\Length(max: 255, maxMessage: 'common.string.max-length', groups: ['notification_interception'])]
    #[AppAssert\CommaSeparatedEmails(message: "common.email.list-contains-invalid", groups: ["notification_interception"])]
    #[ORM\Column(type: Types::STRING, length: 1024)]
    private ?string $emails = null;

    #[\Override]
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAddressLine(): ?string
    {
        return $this->addressLine;
    }

    public function setAddressLine(?string $addressLine): self
    {
        $this->addressLine = $addressLine;
        return $this;
    }

    #[\Override]
    public function getEmails(): ?string
    {
        return $this->emails;
    }

    #[\Override]
    public function setEmails(?string $emails): self
    {
        $this->emails = $emails;
        return $this;
    }

    #[\Override]
    public function getPrimaryName(): ?string
    {
        return $this->getAddressLine();
    }
}
