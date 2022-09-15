<?php

namespace App\Doctrine\Migrations\Version;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Version\MigrationFactory;
use Google\Cloud\Storage\Bucket;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ProjectMigrationFactory implements MigrationFactory
{
    private MigrationFactory $migrationFactory;
    private Bucket $exportBucket;
    private ContainerInterface $container;

    public function __construct(MigrationFactory $migrationFactory, Bucket $exportBucket, ContainerInterface $container)
    {
        $this->migrationFactory = $migrationFactory;
        $this->exportBucket = $exportBucket;
        $this->container = $container;
    }

    public function createVersion(string $migrationClassName): AbstractMigration
    {
        $instance = $this->migrationFactory->createVersion($migrationClassName);

        if (method_exists($instance, "setExportBucket")) {
            $instance->setExportBucket($this->exportBucket);
        }

        if ($instance instanceof ContainerAwareInterface) {
            $instance->setContainer($this->container);
        }

        return $instance;
    }
}