<?php

namespace App\Entity\Domestic;

use App\Repository\Domestic\NotificationInterceptionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=NotificationInterceptionRepository::class)
 * @ORM\Table("domestic_notification_interception")
 */
class NotificationInterception
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(groups={"notification_interception"}, message="Address line must not be empty")
     * @Assert\Regex(groups={"notification_interception"}, message="Address lines must not start with a symbol (@ ( ) = [ ] "" , < > \ /)", pattern="/^[@()=\[\]"",<>\\\/]/", match=false)
     * @Assert\Length(max=255, maxMessage="common.string.max-length", groups={"notification_interception"})
     */
    private ?string $addressLine;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank (groups={"notification_interception"})
     * @Assert\Email(groups={"notification_interception"})
     * @Assert\Length(max=255, maxMessage="common.string.max-length", groups={"notification_interception"})
     */
    private ?string $email;

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

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }
}
