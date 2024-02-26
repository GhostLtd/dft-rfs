<?php

namespace App\Entity;

use App\Form\Validator as AppAssert;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

trait NotificationInterceptionTrait
{
    #[Assert\NotBlank(groups: ['notification_interception'])]
    #[Assert\Length(max: 255, maxMessage: 'common.string.max-length', groups: ['notification_interception'])]
    #[AppAssert\CommaSeparatedEmails(message: "common.email.list-contains-invalid", groups: ["notification_interception"])]
    #[ORM\Column(type: Types::STRING, length: 1024)]
    private ?string $emails;

    public function getEmails(): ?string
    {
        return $this->emails;
    }

    public function setEmails(?string $emails): self
    {
        $this->emails = $emails;
        return $this;
    }
}
