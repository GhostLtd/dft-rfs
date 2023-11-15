<?php

namespace App\Entity;

use App\Entity\NotifyApiResponse;
use App\Entity\RoRo\Operator;
use App\Entity\Route\Route;
use App\Repository\RoRoUserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=RoRoUserRepository::class)
 * @ORM\Table(name="roro_user")
 * @UniqueEntity("username", message="roro.user.username.already-in-use", groups={"admin_add_roro_user"})
 */
class RoRoUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    const ROLE_RORO_USER = 'ROLE_RORO_USER';

    use IdTrait;
    use NotifyApiResponseTrait;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Assert\NotBlank(message="roro.user.username.not-null", groups={"admin_add_roro_user"})
     */
    private ?string $username = null;


    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTime $lastLogin = null;

    /**
     * @ORM\ManyToOne(targetEntity=Operator::class, inversedBy="users")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?Operator $operator = null;

    /**
     * @ORM\ManyToMany(targetEntity=NotifyApiResponse::class)
     * @ORM\JoinTable(
     *     name="roro_user_notify_api_responses",
     *     joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="notify_api_response_id", referencedColumnName="id", unique=true, onDelete="CASCADE")}
     * )
     */
    protected Collection $apiResponses;

    public function __construct()
    {
        $this->apiResponses = new ArrayCollection();
    }

    public function getUserIdentifier(): ?string
    {
        return $this->username;
    }

    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    /**
     * Used by login_link
     */
    public function getEmail(): string
    {
        return $this->getUserIdentifier();
    }

    public function setUsername(?string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function getRoles(): array
    {
        return [self::ROLE_RORO_USER];
    }

    public function getOperator(): ?Operator
    {
        return $this->operator;
    }

    public function setOperator(?Operator $operator): self
    {
        $this->operator = $operator;
        return $this;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function eraseCredentials(): void
    {
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

    public function getLastLogin(): ?\DateTimeInterface
    {
        return $this->lastLogin;
    }

    public function setLastLogin(?\DateTimeInterface $lastLogin): self
    {
        $this->lastLogin = $lastLogin;
        return $this;
    }

    public function getPassword(): ?string
    {
        return null;
    }
}
