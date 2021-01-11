<?php

namespace App\Entity;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\International\Survey as InternationalSurvey;
use App\Repository\PasscodeUserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass=PasscodeUserRepository::class)
 */
class PasscodeUser implements UserInterface
{
    const ROLE_PASSCODE_USER = 'ROLE_PASSCODE_USER';
    const ROLE_DOMESTIC_SURVEY_USER = 'ROLE_DOMESTIC_SURVEY_USER';
    const ROLE_INTERNATIONAL_SURVEY_USER = 'ROLE_INTERNATIONAL_SURVEY_USER';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     * @ORM\Column(type="guid", unique=true)
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=10, unique=true)
     */
    private $username;

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * @var string | null
     */
    private $plainPassword;

    /**
     * @ORM\OneToOne(targetEntity=DomesticSurvey::class, inversedBy="passcodeUser", cascade={"persist"}, fetch="EAGER")
     */
    private $domesticSurvey;

    /**
     * @ORM\OneToOne(targetEntity=InternationalSurvey::class, inversedBy="passcodeUser", cascade={"persist"})
     */
    private $internationalSurvey;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @see UserInterface
     */
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
        }

        return array_unique($roles);
    }

    public function hasRole($role)
    {
        return in_array($role, $this->getRoles());
    }

    public function setRoles(array $roles): self
    {
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
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

    /**
     * @return string|null
     */
    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    /**
     * @param string|null $plainPassword
     * @return PasscodeUser
     */
    public function setPlainPassword(?string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;
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
}
