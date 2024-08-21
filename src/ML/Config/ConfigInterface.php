<?php

namespace App\ML\Config;

use App\ML\FileMapping;
use App\ML\ValidationException;

interface ConfigInterface
{
    public function getName(): string;
    public function getDescription(): string;

    /**
     * @throws ValidationException
     */
    public function normalizeAndErrorCheck(array $row): array;
    public function getColumnsForFileTypeConfig(string $type): ?array;
    public function getCombinedColumnNames(): array;
    public function getFiletypesAndColumnsConfig(): array;
    public function getTargetColumnName(): string;

    public function shouldOutputHeader(): bool;
}