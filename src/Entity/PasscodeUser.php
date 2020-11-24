<?php

namespace App\Entity;

use App\Entity\Domestic\Survey;
use App\Repository\PasscodeUserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass=PasscodeUserRepository::class)
 */
class PasscodeUser implements UserInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer", length=10, unique=true)
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
     * @ORM\OneToOne(targetEntity=Survey::class, inversedBy="passcodeUser", cascade={"persist"}, fetch="EAGER")
     */
    private $domesticSurvey;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?int
    {
        return $this->username;
    }

    public function setUsername(int $username): self
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
        $roles[] = 'ROLE_PASSCODE_USER';

        if ($this->getDomesticSurvey()) {
            $roles[] = 'ROLE_DOMESTIC_SURVEY_USER';
        }

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

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

    public function getDomesticSurvey(): ?Survey
    {
        return $this->domesticSurvey;
    }

    public function setDomesticSurvey(?Survey $domesticSurvey): self
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
}
