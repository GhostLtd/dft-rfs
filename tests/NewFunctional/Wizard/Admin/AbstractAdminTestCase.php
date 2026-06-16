<?php

namespace App\Tests\NewFunctional\Wizard\Admin;

use App\Entity\Domestic\Survey;
use App\Tests\NewFunctional\AbstractProceduralWizardTestCase;
use App\Tests\NewFunctional\Wizard\Action\Context;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class AbstractAdminTestCase extends AbstractProceduralWizardTestCase
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

    protected function assertEntityCountInDatabase(string $entityClass, int $count): void
    {
        $this->callbackTestAction(function (Context $context) use ($entityClass, $count) {
            $entities = $context->getEntityManager()->getRepository($entityClass)->findAll();

            $context->getTestCase()->assertCount($count, $entities, "Expected number of {$entityClass} in the database to be {$count}.");
        });
    }

}
