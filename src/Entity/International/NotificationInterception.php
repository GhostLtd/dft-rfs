<?php

namespace App\Entity\International;

use App\Entity\IdTrait;
use App\Entity\NotificationInterceptionAdvancedInterface;
use App\Entity\NotificationInterceptionCompanyNameInterface;
use App\Repository\International\NotificationInterceptionRepository;
use App\Entity\NotificationInterceptionTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=NotificationInterceptionRepository::class)
 * @ORM\Table("international_notification_interception")
 */
class NotificationInterception implements NotificationInterceptionAdvancedInterface
{
    use IdTrait;
    use NotificationInterceptionTrait;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Assert\NotBlank(groups={"notification_interception"}, message="Company name must not be empty")
     * @Assert\Regex(groups={"notification_interception"}, message="Company name must not start with a symbol (@ ( ) = [ ] "" , < > \ /)", pattern="/^[@()=\[\]"",<>\\\/]/", match=false)
     * @Assert\Length(max=255, maxMessage="common.string.max-length", groups={"notification_interception"})
     */
    private ?string $primaryName;

    /**
     * @var null|Collection|NotificationInterceptionCompanyName[]
     * @ORM\OneToMany(targetEntity=NotificationInterceptionCompanyName::class, mappedBy="notificationInterception", orphanRemoval=true, cascade={"all"})
     * @Assert\Valid(groups={"notification_interception"})
     * @ORM\OrderBy({"name" = "ASC"})
     */
    private ?Collection $additionalNames;

    public function __construct()
    {
        $this->additionalNames = new ArrayCollection();
    }

    /**
     * @return Collection|NotificationInterceptionCompanyName[]
     */
    public function getAdditionalNames(): Collection
    {
        return $this->additionalNames;
    }

    /**
     * @param NotificationInterceptionCompanyName $companyName
     * @return $this
     */
    public function addAdditionalName(NotificationInterceptionCompanyNameInterface $companyName): self
    {
        if (!$this->additionalNames->contains($companyName)) {
            $this->additionalNames[] = $companyName;
            $companyName->setNotificationInterception($this);
        }

        return $this;
    }

    /**
     * @param NotificationInterceptionCompanyName $companyName
     * @return $this
     */
    public function removeAdditionalName(NotificationInterceptionCompanyNameInterface $companyName): self
    {
        if ($this->additionalNames->removeElement($companyName)) {
            // set the owning side to null (unless already changed)
            if ($companyName->getNotificationInterception() === $this) {
                $companyName->setNotificationInterception(null);
            }
        }

        return $this;
    }

    public function getPrimaryName(): ?string
    {
        return $this->primaryName ?? null;
    }

    public function setPrimaryName(?string $primaryName): self
    {
        $this->primaryName = $primaryName;
        return $this;
    }
}