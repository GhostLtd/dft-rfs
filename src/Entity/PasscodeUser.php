<?php

namespace App\Entity;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Repository\PasscodeUserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: PasscodeUserRepository::class)]
class PasscodeUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLE_PASSCODE_USER = 'ROLE_PASSCODE_USER';
    public const ROLE_DOMESTIC_SURVEY_USER = 'ROLE_DOMESTIC_SURVEY_USER';
    public const ROLE_INTERNATIONAL_SURVEY_USER = 'ROLE_INTERNATIONAL_SURVEY_USER';
    public const ROLE_PRE_ENQUIRY_USER = 'ROLE_PRE_ENQUIRY_USER';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: Types::STRING, length: 36, unique: true, options: ['fixed' => true])]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?string $id = null;

    #[ORM\Column(type: Types::STRING, length: 10, unique: true)]
    private ?string $username = null;

    /**
     * @var ?string The hashed password
     */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $password = null;

    private ?string $plainPassword = null;

    #[ORM\OneToOne(inversedBy: 'passcodeUser', targetEntity: DomesticSurvey::class, cascade: ['persist'], fetch: 'EAGER')]
    private ?DomesticSurvey $domesticSurvey = null;

    #[ORM\OneToOne(inversedBy: 'passcodeUser', targetEntity: InternationalSurvey::class, cascade: ['persist'], fetch: 'EAGER')]
    private ?InternationalSurvey $internationalSurvey = null;

    #[ORM\OneToOne(inversedBy: 'passcodeUser', targetEntity: PreEnquiry::class, cascade: ['persist'], fetch: 'EAGER')]
    private ?PreEnquiry $preEnquiry = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastLogin = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    #[\Override]
    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    public function setUsername(?string $username): self
    {
        $this->username = $username;
        return $this;
    }

    #[\Override]
    public function getRoles(): array
    {
        // guarantee every user at least has ROLE_USER
        $roles[] = self::ROLE_PASSCODE_USER;

        switch(true) {
            case $this->getDomesticSurvey() :
                $roles[] = self::ROLE_DOMESTIC_SURVEY_USER;
                break;

            case $this->getInternationalSurvey() :
                $roles[] = self::ROLE_INTERNATIONAL_SURVEY_USER;
                break;

            case $this->getPreEnquiry() :
                $roles[] = self::ROLE_PRE_ENQUIRY_USER;
        }

        return array_unique($roles);
    }

    #[\Override]
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    #[\Override]
    public function eraseCredentials(): void
    {
    }

    public function getDomesticSurvey(): ?DomesticSurvey
    {
        return $this->domesticSurvey;
    }

    public function setDomesticSurvey(?DomesticSurvey $domesticSurvey): self
    {
        $this->domesticSurvey = $domesticSurvey;
        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;

        // To make sure that entityManager tries to flush this change, so that the password-hash subscriber gets triggered.
        $this->password = null;
        return $this;
    }

    public function getInternationalSurvey(): ?InternationalSurvey
    {
        return $this->internationalSurvey;
    }

    public function setInternationalSurvey(?InternationalSurvey $internationalSurvey): self
    {
        $this->internationalSurvey = $internationalSurvey;

        return $this;
    }

    public function getPreEnquiry(): ?PreEnquiry
    {
        return $this->preEnquiry;
    }

    public function setPreEnquiry($preEnquiry): self
    {
        $this->preEnquiry = $preEnquiry;
        return $this;
    }

    public function getLastLogin(): ?\DateTimeInterface
    {
        return $this->lastLogin;
    }

    public function setLastLogin(?\DateTimeInterface $lastLogin): self
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    public function getSurvey(): ?SurveyInterface
    {
        if ($this->domesticSurvey) {
            return $this->domesticSurvey;
        } else if ($this->internationalSurvey) {
            return $this->internationalSurvey;
        } else if ($this->preEnquiry) {
            return $this->preEnquiry;
        }

        return null;
    }

    public function getSurveyId(): ?string
    {
        return $this->getSurvey()?->getId();
    }
}
