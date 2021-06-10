<?php

namespace App\Tests\Functional\Deployment;

use App\Tests\DataFixtures\DoctrineMigrationFixtures;
use App\Tests\Functional\AbstractFrontendFunctionalTest;
use App\Utility\RemoteActions;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Process\Process;

class RemoteActionsPreInstallTest extends AbstractFrontendFunctionalTest
{
    protected function tearDown(): void
    {
        // delete any migration files that have been created!!
    }

    public function testNoMigrations()
    {
        $kernel = static::bootKernel();
        $this->loadFixtures([DoctrineMigrationFixtures::class]);

        self::assertNotFalse(RemoteActions::preInstall());
    }

    public function testMigrationsNotMaintenance()
    {
        $kernel = static::bootKernel();
        $this->loadFixtures([DoctrineMigrationFixtures::class]);

        //create new migration file
        $process = new Process(['bin/console', 'doctrine:migrations:generate']);
        $process->run();
        dump($process->getOutput());


        try {
            RemoteActions::preInstall();
        } catch (HttpException $e) {
            self::assertSame(500, $e->getStatusCode());
            dump($e->getMessage());
            return;
        }
        throw new \AssertionError("The init script was not expected to complete");
    }
}
