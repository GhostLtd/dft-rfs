<?php

namespace App\ML\FileType;

interface FileTypeInterface
{
    public function getColumnsNames(): array;
    public function getName(): string;
}