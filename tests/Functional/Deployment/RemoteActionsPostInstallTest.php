<?php

namespace App\Tests\Functional\Deployment;

use App\Tests\DataFixtures\DoctrineMigrationFixtures;
use App\Tests\DataFixtures\MaintenanceLockFixtures;
use App\Tests\Functional\AbstractFunctionalTest;
use App\Utility\RemoteActions;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Process\Process;

class RemoteActionsPostInstallTest extends AbstractFunctionalTest
{
    private array $addedMigrations = [];

    protected function tearDown(): void
    {
        parent::tearDown();

        // delete any migration files that have been created!!
        $filesystem = new Filesystem();
        $filesystem->remove($this->addedMigrations);
    }

    public function testNoMigrations()
    {
        $kernel = static::bootKernel();
        $this->loadFixtures([DoctrineMigrationFixtures::class]);

        self::assertStringContainsString('Post-install script completed successfully.', RemoteActions::postInstall());
    }

    public function testMigrations()
    {
        $kernel = static::bootKernel();
        $this->loadFixtures([DoctrineMigrationFixtures::class]);

        $this->generateMigration();

        self::assertStringContainsString('Post-install script completed successfully.', RemoteActions::postInstall());
    }

    private function generateMigration()
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
