<?php

namespace App\Entity\International;

use App\Entity\IdTrait;
use App\Entity\NotificationInterceptionAdvancedInterface;
use App\Entity\NotificationInterceptionCompanyNameInterface;
use App\Repository\International\NotificationInterceptionRepository;
use App\Entity\NotificationInterceptionTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table('international_notification_interception')]
#[ORM\Entity(repositoryClass: NotificationInterceptionRepository::class)]
class NotificationInterception implements NotificationInterceptionAdvancedInterface
{
    use IdTrait;
    use NotificationInterceptionTrait;

    /**
     * N.B. Validation of name uniqueness is handled by the validator in NotificationInterceptionAdvancedInterface,
     * since each IRHS LCNI can have multiple names.
     */
    #[Assert\NotBlank(message: 'Company name must not be empty', groups: ['notification_interception'])]
    #[Assert\Regex("#^[@()=\[\]\",<>\\\/]#", message: 'Company name must not start with a symbol (@ ( ) = [ ] "" , < > \ /)', match: false, groups: ['notification_interception'])]
    #[Assert\Length(max: 255, maxMessage: 'common.string.max-length', groups: ['notification_interception'])]
    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    private ?string $primaryName = null;

    /**
     * @var ?Collection<NotificationInterceptionCompanyName>
     */
    #[Assert\Valid(groups: ['notification_interception'])]
    #[ORM\OneToMany(mappedBy: 'notificationInterception', targetEntity: NotificationInterceptionCompanyName::class, cascade: ['all'], orphanRemoval: true)]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private ?Collection $additionalNames;

    public function __construct()
    {
        $this->additionalNames = new ArrayCollection();
    }

    /**
     * @return Collection<NotificationInterceptionCompanyName>
     */
    #[\Override]
    public function getAdditionalNames(): Collection
    {
        return $this->additionalNames;
    }

    #[\Override]
    public function addAdditionalName(NotificationInterceptionCompanyNameInterface $companyName): self
    {
        if (!$this->additionalNames->contains($companyName)) {
            $this->additionalNames->add($companyName);
            $companyName->setNotificationInterception($this);
        }

        return $this;
    }

    #[\Override]
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

    #[\Override]
    public function getPrimaryName(): ?string
    {
        return $this->primaryName ?? null;
    }

    public function setPrimaryName(?string $primaryName): self
    {
        $this->primaryName = $primaryName;
        return $this;
    }

    public function getAllNames(): array
    {
        return [
            $this->getPrimaryName(),
            ...array_map(fn(NotificationInterceptionCompanyName $cn) => $cn->getName(), $this->getAdditionalNames()->toArray())
        ];
    }
}
