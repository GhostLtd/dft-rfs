<?php

namespace App\Tests\Security\Voter;

use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

abstract class AbstractVoterTest extends WebTestCase
{
    protected AuthorizationCheckerInterface $authChecker;
    protected AbstractDatabaseTool $databaseTool;
    protected ReferenceRepository $fixtureReferenceRepository;
    protected TokenStorageInterface $tokenStorage;

    #[\Override]
    protected function setUp(): void
    {
        $container = static::getContainer();
        $this->authChecker = $container->get('security.authorization_checker');
        $this->databaseTool = $container->get(DatabaseToolCollection::class)->get();
        $this->tokenStorage = $container->get('security.token_storage');
    }

    protected function loadFixtures(array $classNames = [], bool $append = false): ?AbstractExecutor
    {
        $fixtures = $this->databaseTool->loadFixtures($classNames, $append);
        $this->fixtureReferenceRepository = $fixtures->getReferenceRepository();

        return $fixtures;
    }

    protected function createAndStorePasscodeUserToken(UserInterface $user): void
    {
        $token = new PostAuthenticationToken($user, 'main', $user->getRoles());
        $this->tokenStorage->setToken($token);
    }

    protected function createAndStoreAdminUserToken(): void
    {
        $user = new InMemoryUser('admin', '', ['ROLE_ADMIN_FORM_USER']);
        $token = new UsernamePasswordToken($user, 'admin', $user->getRoles());
        $this->tokenStorage->setToken($token);
    }
}
