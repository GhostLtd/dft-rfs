<?php

namespace App\Entity\International;

use App\Entity\IdTrait;
use App\Entity\NotificationInterceptionCompanyNameInterface;
use App\Entity\NotificationInterceptionInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @ORM\Table("international_notification_interception_company_name")
 */
class NotificationInterceptionCompanyName implements NotificationInterceptionCompanyNameInterface
{
    use IdTrait;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Assert\NotBlank(groups={"notification_interception"}, message="Company name must not be empty")
     * @Assert\Regex(groups={"notification_interception"}, message="Company name must not start with a symbol (@ ( ) = [ ] "" , < > \ /)", pattern="/^[@()=\[\]"",<>\\\/]/", match=false)
     * @Assert\Length(max=255, maxMessage="common.string.max-length", groups={"notification_interception"})
     */
    private ?string $name;

    /**
     * @ORM\ManyToOne(targetEntity=NotificationInterception::class, inversedBy="additionalNames", cascade={"all"})
     * @ORM\JoinColumn(nullable=false)
     */
    private ?NotificationInterception $notificationInterception;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return NotificationInterception|null
     */
    public function getNotificationInterception(): ?NotificationInterceptionInterface
    {
        return $this->notificationInterception;
    }

    /**
     * @param NotificationInterception|null $notificationInterception
     * @return $this
     */
    public function setNotificationInterception(?NotificationInterceptionInterface $notificationInterception): self
    {
        $this->notificationInterception = $notificationInterception;

        return $this;
    }
}
