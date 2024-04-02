<?php

namespace App\Tests\NewFunctional\Wizard\Admin;

use App\Tests\NewFunctional\AbstractProceduralWizardTest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class AbstractAdminTest extends AbstractProceduralWizardTest
{
    protected EntityManagerInterface $entityManager;

    protected function initialiseTest(array $fixtures): void
    {
        $this->entityManager = KernelTestCase::getContainer()->get(EntityManagerInterface::class);
        $this->initialiseClientAndLoadFixtures($fixtures, [
            'hostname' => 'rfs-admin.localhost',
            'env' => ['TEST_GOOGLE_IAP_AUTH_USER' => 'test@example.com'],
        ]);
        $this->context = $this->createContext('');

        $this->client->request('GET', '/');
    }
}
