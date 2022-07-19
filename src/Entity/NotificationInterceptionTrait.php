<?php

namespace App\Entity;

use App\Form\Validator as AppAssert;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

trait NotificationInterceptionTrait
{
    /**
     * @ORM\Column(type="string", length=1024)
     * @Assert\NotBlank (groups={"notification_interception"})
     * @AppAssert\CommaSeparatedEmails(groups={"notification_interception"}, message="common.email.list-contains-invalid")
     * @Assert\Length(max=255, maxMessage="common.string.max-length", groups={"notification_interception"})
     */
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
