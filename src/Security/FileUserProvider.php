<?php

namespace App\Security;

use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class FileUserProvider implements UserProviderInterface
{
    /** @var InMemoryUser[] */
    protected array $users = [];

    public function __construct(?string $usersFile, protected ManagementUserHelper $managementUserHelper)
    {
        $this->loadUsersFromFile($usersFile);
    }

    protected function loadUsersFromFile(?string $usersFile): void
    {
        if ($usersFile === null || !file_exists($usersFile)) {
            return;
        }

        $userFileLines = file($usersFile);
        foreach ($userFileLines as $line)
        {
            $userParts = str_getcsv($line, ':');
            if (count($userParts) === 2)
            {
                $username = trim($userParts[0]);
                $password = trim($userParts[1]);

                $role = $this->managementUserHelper->isManagementDomain($username) ?
                    'ROLE_ADMIN_FORM_MANAGER' :
                    'ROLE_ADMIN_FORM_USER';

                $user = new InMemoryUser($username, $password, [$role]);
                $this->users[$username] = $user;
            }
        }
    }

    #[\Override]
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        if (array_key_exists($identifier, $this->users)) {
            return $this->users[$identifier];
        }

        throw new UserNotFoundException(
            sprintf('Username "%s" does not exist.', $identifier)
        );
    }

    public function loadUserByUsername(string $username): UserInterface
    {
        return $this->loadUserByIdentifier($username);
    }

    #[\Override]
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof InMemoryUser) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', $user::class)
            );
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    #[\Override]
    public function supportsClass($class): bool
    {
        return $class === InMemoryUser::class;
    }
}
