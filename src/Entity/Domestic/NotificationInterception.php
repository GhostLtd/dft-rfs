<?php

namespace App\Entity\Domestic;

use App\Entity\NotificationInterceptionInterface;
use App\Repository\Domestic\NotificationInterceptionRepository;
use App\Form\Validator as AppAssert;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=NotificationInterceptionRepository::class)
 * @ORM\Table("domestic_notification_interception")
 */
class NotificationInterception implements NotificationInterceptionInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Assert\NotBlank(groups={"notification_interception"}, message="Address line must not be empty")
     * @Assert\Regex(groups={"notification_interception"}, message="Address lines must not start with a symbol (@ ( ) = [ ] "" , < > \ /)", pattern="/^[@()=\[\]"",<>\\\/]/", match=false)
     * @Assert\Length(max=255, maxMessage="common.string.max-length", groups={"notification_interception"})
     */
    private ?string $addressLine;

    /**
     * @ORM\Column(type="string", length=1024)
     * @Assert\NotBlank (groups={"notification_interception"})
     * @AppAssert\CommaSeparatedEmails(groups={"notification_interception"}, message="common.email.list-contains-invalid")
     * @Assert\Length(max=255, maxMessage="common.string.max-length", groups={"notification_interception"})
     */
    private ?string $emails;

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

    public function getEmails(): ?string
    {
        return $this->emails;
    }

    public function setEmails(?string $emails): self
    {
        $this->emails = $emails;
        return $this;
    }

    public function getPrimaryName(): ?string
    {
        return $this->getAddressLine();
    }
}
