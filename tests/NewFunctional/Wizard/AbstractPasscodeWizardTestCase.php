<?php

namespace App\Tests\NewFunctional\Wizard;

use App\Entity\PasscodeUser;
use App\Tests\NewFunctional\AbstractProceduralWizardTestCase;
use App\Utility\PasscodeGenerator;
use Doctrine\ORM\EntityManagerInterface;

abstract class AbstractPasscodeWizardTestCase extends AbstractProceduralWizardTestCase
{
    protected EntityManagerInterface $entityManager;
    private PasscodeGenerator $passcodeGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->passcodeGenerator = $this->getContainer()->get(PasscodeGenerator::class);
    }

    protected function initialiseTest(array $fixtures): void
    {
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->initialiseClientAndLoadFixtures($fixtures);
        $this->context = $this->createContext('');
        $user = $this->fixtureReferenceRepository->getReference('user:frontend', PasscodeUser::class);
        $this->passcodeLogin($user->getUsername(), $this->passcodeGenerator->getPasswordForUser($user));
    }

    #[\Override]
    public function tearDown(): void
    {
        try {
            $this->client->request('GET', '/logout');
        } catch(\Exception) {}

        parent::tearDown();
    }

    protected function passcodeLogin(string $passcodeOne, string $passcodeTwo, bool $logoutFirst = true): void
    {
        if ($logoutFirst) {
            $this->client->request('GET', '/logout'); // Make sure we're logged out
        }

        $this->context
            ->outputHeader('Logging in')
            ->increaseActionIndex();

        $this->client->request('GET', '/login');
        $this->runAndWaitForNavigation(fn() => $this->client->submitForm('passcode_login_sign_in', [
            'passcode_login[passcode][0]' => $passcodeOne,
            'passcode_login[passcode][1]' => $passcodeTwo,
        ]));
    }
}