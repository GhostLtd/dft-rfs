<?php

namespace App\Security;


use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class FileUserProvider implements UserProviderInterface
{
    /** @var User[] */
    protected $users = [];

    public function __construct($usersFile)
    {
        $this->loadUsersFromFile($usersFile);
    }

    protected function loadUsersFromFile($usersFile)
    {
        if (is_null($usersFile)) return;
        if (!file_exists($usersFile)) return;
        $userFileLines = file($usersFile);
        foreach ($userFileLines as $line)
        {
            $userParts = str_getcsv($line, ':');
            if (count($userParts) === 2)
            {
                $username = trim($userParts[0]);
                $password = trim($userParts[1]);
                $user = new User($username, $password, ['ROLE_ADMIN_USER']);
                $this->users[$username] = $user;
            }
        }
    }

    public function loadUserByUsername($username)
    {
        if (array_key_exists($username, $this->users)) {
            return $this->users[$username];
        }

        throw new UsernameNotFoundException(
            sprintf('Username "%s" does not exist.', $username)
        );
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    public function supportsClass($class)
    {
        return $class === User::class;
    }
}
