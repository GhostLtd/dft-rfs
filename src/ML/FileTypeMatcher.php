<?php

namespace App\ML;

use App\ML\Config\ConfigInterface;
use App\ML\FileType\FileTypeInterface;

class FileTypeMatcher
{
    protected array $fileTypes;

    public function __construct()
    {
        $this->fileTypes = [];
    }

    public function addFileType(FileTypeInterface $fileType): void
    {
        $this->fileTypes[$fileType->getName()] = $fileType->getColumnsNames();
    }

    public function getFileType(array $headers): ?string
    {
        foreach($this->fileTypes as $name => $columns) {
            $isMatch = true;

            foreach($columns as $column) {
                if (!in_array($column, $headers)) {
                    $isMatch = false;
                    break;
                }
            }

            if ($isMatch) {
                return $name;
            }
        }

        return null;
    }

    public function getColumnNamesForFileType(string $fileType): ?array
    {
        return $this->fileTypes[$fileType] ?? null;
    }

    public function getFileMapping(array $headers, ConfigInterface $config): ?FileMapping
    {
        $type = $this->getFileType($headers);

        if (!$type) {
            return null;
        }

        $columns = $config->getColumnsForFileTypeConfig($type);
        $headers =  $this->getColumnNamesForFileType($type);

        $mapping = [];
        foreach($columns as $column => $options) {
            $key = array_search($column, $headers);

            $mapping[] = [
                'header' => $options['rename'] ?? $column,
                'key' => $key
            ];
        }

        return new FileMapping($type, $mapping);
    }
}