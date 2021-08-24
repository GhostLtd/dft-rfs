<?php


namespace App\Doctrine\Migrations\Version;


use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Version\MigrationFactory;
use Google\Cloud\Storage\Bucket;

class ProjectMigrationFactory implements MigrationFactory
{
    private MigrationFactory $migrationFactory;
    private Bucket $exportBucket;

    public function __construct(MigrationFactory $migrationFactory, Bucket $exportBucket)
    {
        $this->migrationFactory = $migrationFactory;
        $this->exportBucket = $exportBucket;
    }

    public function createVersion(string $migrationClassName): AbstractMigration
    {
        $instance = $this->migrationFactory->createVersion($migrationClassName);

        if (method_exists($instance, "setExportBucket")) {
            $instance->setExportBucket($this->exportBucket);
        }

        return $instance;
    }
}