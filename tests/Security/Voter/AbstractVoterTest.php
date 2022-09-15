<?php


namespace App\Tests\Security\Voter;


use App\Entity\PasscodeUser;
use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Liip\TestFixturesBundle\Test\FixturesTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;

abstract class AbstractVoterTest extends WebTestCase
{
    use FixturesTrait {
        loadFixtures as _loadFixtures;
    }

    protected AuthorizationCheckerInterface $authChecker;
    protected ReferenceRepository $fixtureReferenceRepository;
    protected TokenStorageInterface $tokenStorage;

    public function setUp()
    {
        static::bootKernel();

        $container = self::$kernel->getContainer()->get('test.service_container');
        $this->authChecker = $container->get('security.authorization_checker');
        $this->tokenStorage = $container->get('security.token_storage');
    }

    protected function loadFixtures(array $classNames = [], bool $append = false, ?string $omName = null, string $registryName = 'doctrine', ?int $purgeMode = null): ?AbstractExecutor
    {
        $fixtures = $this->_loadFixtures($classNames, $append, $omName, $registryName, $purgeMode);
        $this->fixtureReferenceRepository = $fixtures->getReferenceRepository();

        return $fixtures;
    }

    protected function createAndStorePasscodeUserToken(PasscodeUser $user) {
        $token = new PostAuthenticationGuardToken($user, 'main', $user->getRoles());
        $this->tokenStorage->setToken($token);
    }

    protected function createAndStoreAdminUserToken()
    {
        $user = new User('admin', '', ['ROLE_ADMIN_FORM_USER']);
        $token = new UsernamePasswordToken($user, null, 'admin', $user->getRoles());
        $this->tokenStorage->setToken($token);
    }
}