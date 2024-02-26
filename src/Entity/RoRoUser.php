<?php

namespace App\Entity;

use App\Entity\NotifyApiResponse;
use App\Entity\RoRo\Operator;
use App\Entity\Route\Route;
use App\Repository\RoRoUserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[UniqueEntity('username', message: 'roro.user.username.already-in-use', groups: ['admin_add_roro_user'])]
#[ORM\Table(name: 'roro_user')]
#[ORM\Entity(repositoryClass: RoRoUserRepository::class)]
class RoRoUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLE_RORO_USER = 'ROLE_RORO_USER';

    use IdTrait;
    use NotifyApiResponseTrait;

    #[Assert\NotBlank(message: 'roro.user.username.not-null', groups: ['admin_add_roro_user'])]
    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    private ?string $username = null;


    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $lastLogin = null;

    #[ORM\JoinColumn(nullable: false)]
    #[ORM\ManyToOne(targetEntity: Operator::class, inversedBy: 'users')]
    private ?Operator $operator = null;

    /**
     * @var Collection<int, NotifyApiResponse>
     */
    #[ORM\JoinTable(name: 'roro_user_notify_api_responses')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'notify_api_response_id', referencedColumnName: 'id', unique: true, onDelete: 'CASCADE')]
    #[ORM\ManyToMany(targetEntity: NotifyApiResponse::class)]
    protected Collection $apiResponses;

    public function __construct()
    {
        $this->apiResponses = new ArrayCollection();
    }

    #[\Override]
    public function getUserIdentifier(): string
    {
        return $this->username ?? '';
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

    #[\Override]
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

    #[\Override]
    public function eraseCredentials(): void
    {
    }

    public function getLastLogin(): ?\DateTime
    {
        return $this->lastLogin;
    }

    public function setLastLogin(?\DateTime $lastLogin): self
    {
        $this->lastLogin = $lastLogin;
        return $this;
    }

    #[\Override]
    public function getPassword(): ?string
    {
        return null;
    }
}
