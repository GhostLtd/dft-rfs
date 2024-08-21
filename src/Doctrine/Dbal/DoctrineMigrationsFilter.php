<?php

namespace App\Doctrine\Dbal;

use Doctrine\DBAL\Schema\AbstractAsset;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\Migrations\Tools\Console\Command\DoctrineCommand;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Prevents d:s:u from trying to drop doctrine_migration_versions whilst avoiding breaking migrations entirely
 * (as would happen if it were added to schema_filter)
 *
 * @see: https://github.com/doctrine/DoctrineMigrationsBundle/issues/478
 */
#[AutoconfigureTag('doctrine.dbal.schema_filter')]
class DoctrineMigrationsFilter implements EventSubscriberInterface
{
    private bool $enabled = true;

    public function __invoke(AbstractAsset|string $asset): bool
    {
        if (!$this->enabled) {
            return true;
        }
        if (!class_exists(TableMetadataStorageConfiguration::class)) {
            return true;
        }
        if ($asset instanceof AbstractAsset) {
            $asset = $asset->getName();
        }
        return $asset !== (new TableMetadataStorageConfiguration())->getTableName();
    }

    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();
        if ($command === null) {
            return;
        }
        /**
         * Any console commands from the Doctrine Migrations bundle may attempt
         * to initialize migrations information storage table. Because of this
         * they should not be affected by this filter because their logic may
         * get broken since they will not "see" the table, they may try to use
         */
        if ($command instanceof DoctrineCommand) {
            $this->enabled = false;
        }
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => 'onConsoleCommand',
        ];
    }
}
