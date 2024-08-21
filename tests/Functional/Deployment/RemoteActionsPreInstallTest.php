<?php

namespace App\Tests\Functional\Deployment;

use App\Tests\DataFixtures\DoctrineMigrationFixtures;
use App\Tests\DataFixtures\MaintenanceLockFixtures;
use App\Tests\Functional\AbstractFunctionalTest;
use App\Utility\RemoteActions;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Process\Process;

class RemoteActionsPreInstallTest extends AbstractFunctionalTest
{
    private array $addedMigrations = [];

    #[\Override]
    protected function tearDown(): void
    {
        parent::tearDown();

        // delete any migration files that have been created!!
        $filesystem = new Filesystem();
        $filesystem->remove($this->addedMigrations);

        self::ensureKernelShutdown();
    }

    public function testNoMigrationsNotMaintenance()
    {
        $this->loadFixtures([DoctrineMigrationFixtures::class]);

        try {
            self::assertStringNotContainsString('Pre-install checks have passed.', RemoteActions::preInstall(), 'PreInstall checks should not pass');
        } catch (HttpException $e) {
            self::assertSame(500, $e->getStatusCode());
            return;
        }
    }

    public function testNoMigrationsMaintenance()
    {
        $this->loadFixtures([DoctrineMigrationFixtures::class, MaintenanceLockFixtures::class]);

        self::assertStringContainsString('Pre-install checks have passed.', RemoteActions::preInstall());
    }

    public function testMigrationsNotMaintenance()
    {
        $this->loadFixtures([DoctrineMigrationFixtures::class]);

        $this->generateMigration();

        try {
            self::assertStringNotContainsString('Pre-install checks have passed.', RemoteActions::preInstall(), 'PreInstall checks should not pass');
        } catch (HttpException $e) {
            self::assertSame(500, $e->getStatusCode());
            return;
        }
    }

    public function testMigrationsMaintenance()
    {
        $this->loadFixtures([MaintenanceLockFixtures::class, DoctrineMigrationFixtures::class]);

        $this->generateMigration();

        self::assertStringContainsString('Pre-install checks have passed.', RemoteActions::preInstall());
    }


    private function generateMigration(): void
    {
        // create new migration file
        $process = new Process(['bin/console', 'doctrine:migrations:generate']);
        $process->run(function ($a, $output) {
            if (preg_match('#(?>Generated new migration class to \"(?<filename>[^\"]+)\")#', $output, $matches)) {
                // save the filename of the new migration so we can clean up after the test
                $this->addedMigrations[] = $matches['filename'];
            }
        });
    }
}
