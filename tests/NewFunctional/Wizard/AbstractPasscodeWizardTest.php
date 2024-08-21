<?php

namespace App\Tests\NewFunctional\Wizard;

use App\Tests\NewFunctional\AbstractProceduralWizardTest;
use Doctrine\ORM\EntityManagerInterface;

abstract class AbstractPasscodeWizardTest extends AbstractProceduralWizardTest
{
    protected EntityManagerInterface $entityManager;

    protected function initialiseTest(array $fixtures): void
    {
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->initialiseClientAndLoadFixtures($fixtures);
        $this->context = $this->createContext('');
        $this->passcodeLogin('test', 'test');
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
        $this->client->submitForm('passcode_login_sign_in', [
            'passcode_login[passcode][0]' => $passcodeOne,
            'passcode_login[passcode][1]' => $passcodeTwo,
        ]);
    }
}