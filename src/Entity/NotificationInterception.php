<?php

namespace App\Entity;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\International\Survey as InternationalSurvey;
use App\Repository\NotificationInterceptionRepository;
use App\Utility\EmailHelper;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Form\Validator as AppAssert;

#[ORM\Entity(repositoryClass: NotificationInterceptionRepository::class)]
class NotificationInterception
{
    use IdTrait;

    /**
     * @var ?Collection<NotificationInterceptionCompanyName>
     */
    #[Assert\Valid(groups: ['notification_interception_names'])]
    #[Assert\Count(min: 1, groups: ['notification_interception_names'])]
    #[ORM\OneToMany(targetEntity: NotificationInterceptionCompanyName::class, mappedBy: 'notificationInterception', cascade: ['all'], orphanRemoval: true)]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private ?Collection $names;

    #[Assert\Length(max: 255, maxMessage: 'common.string.max-length', groups: ['notification_interception_emails'])]
    #[AppAssert\CommaSeparatedEmails(message: "common.email.list-contains-invalid", groups: ["notification_interception_emails"])]
    #[ORM\Column(type: Types::STRING, length: 1024, nullable: true)]
    private ?string $csrgtEmails = null;

    #[Assert\Length(max: 255, maxMessage: 'common.string.max-length', groups: ['notification_interception_emails'])]
    #[AppAssert\CommaSeparatedEmails(message: "common.email.list-contains-invalid", groups: ["notification_interception_emails"])]
    #[ORM\Column(type: Types::STRING, length: 1024, nullable: true)]
    private ?string $irhsEmails = null;

    #[ORM\Column(type: Types::STRING, length: 1024, nullable: true)]
    private ?string $dvlaEmails = null;

    public function __construct()
    {
        $this->names = new ArrayCollection();
    }

    /**
     * @return Collection<NotificationInterceptionCompanyName>
     */
    public function getNames(): Collection
    {
        return $this->names;
    }

    public function addName(string|NotificationInterceptionCompanyName $companyName): self
    {
        if (is_string($companyName)) {
            $companyName = (new NotificationInterceptionCompanyName())->setName($companyName);
        }
        if ($this->names
            ->filter(fn(NotificationInterceptionCompanyName $name) => strtolower($name->getName()) === strtolower($companyName->getName()))
            ->isEmpty()
        ) {
            $this->names->add($companyName);
            $companyName->setNotificationInterception($this);
        }

        return $this;
    }

    public function removeName(NotificationInterceptionCompanyName $companyName): self
    {
        if ($this->names->removeElement($companyName)) {
            // set the owning side to null (unless already changed)
            if ($companyName->getNotificationInterception() === $this) {
                $companyName->setNotificationInterception(null);
            }
        }

        return $this;
    }

    public function getAggregatedEmails(string $surveyTypeOrClass): ?string
    {
        return match ($surveyTypeOrClass) {
            DomesticSurvey::class, 'csrgt', 'domestic' => $this->aggregateWithDvlaEmails($this->csrgtEmails),
            InternationalSurvey::class, 'irhs', 'international' => $this->aggregateWithDvlaEmails($this->irhsEmails),
            default => throw new \InvalidArgumentException(sprintf('Unexpected survey type or class "%s"', $surveyTypeOrClass)),
        };
    }

    public function getAggregatedEmailsArray(string $surveyTypeOrClass): array
    {
        return EmailHelper::convertStringListToArray($this->getAggregatedEmails($surveyTypeOrClass));
    }

    protected function aggregateWithDvlaEmails(?string $surveyTypeEmails): ?string
    {
        return EmailHelper::mergeStringLists($surveyTypeEmails, $this->dvlaEmails);
    }

    public function getCsrgtEmails(): ?string
    {
        return $this->csrgtEmails;
    }

    public function setCsrgtEmails(?string $csrgtEmails): self
    {
        $this->csrgtEmails = $csrgtEmails;
        return $this;
    }

    public function getIrhsEmails(): ?string
    {
        return $this->irhsEmails;
    }

    public function setIrhsEmails(?string $irhsEmails): self
    {
        $this->irhsEmails = $irhsEmails;
        return $this;
    }

    public function getDvlaEmails(): ?string
    {
        return $this->dvlaEmails;
    }

    public function setDvlaEmails(?string $dvlaEmails): static
    {
        $this->dvlaEmails = $dvlaEmails;
        return $this;
    }

    public function addDvlaEmail(string $email): static
    {
        $emails = EmailHelper::convertStringListToArray($this->dvlaEmails);
        $emails[] = $email;
        $this->dvlaEmails = EmailHelper::convertArrayListToString($emails);
        return $this;
    }

    public function hasDvlaEmail(string $email): bool
    {
        $emails = EmailHelper::convertStringListToArray($this->dvlaEmails);
        return in_array($email, $emails);
    }
}
