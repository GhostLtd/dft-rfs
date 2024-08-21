<?php

namespace App\ML\Config;

abstract class AbstractConfig implements ConfigInterface
{
    // TODO: This should all be moved outside to another service (i.e. something should be checking the config rather than the config checking itself. The config should be inert)
    public function __construct()
    {
        $this->assertTargetColumnPresentInAllFileTypes();
    }

    #[\Override]
    public function getColumnsForFileTypeConfig(string $type): ?array
    {
        return $this->getFiletypesAndColumnsConfig()[$type] ?? null;
    }

    #[\Override]
    public function getCombinedColumnNames(): array
    {
        $columns = [];

        foreach($this->getFiletypesAndColumnsConfig() as $columnsConfig) {
            foreach($columnsConfig as $column => $options) {
                $name = $options['rename'] ?? $column;
                $columns[$name] = true;
            }
        }

        return array_keys($columns);
    }

    protected function assertTargetColumnPresentInAllFileTypes(): void
    {
        $target = $this->getTargetColumnName();

        foreach($this->getFiletypesAndColumnsConfig() as $type => $columns) {
            $found = false;
            foreach($columns as $column => $options) {
                $name = $options['rename'] ?? $column;
                if ($name === $target) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                throw new \RuntimeException("Target column '$target' not found in columns for file type '$type'");
            }
        }
    }
}