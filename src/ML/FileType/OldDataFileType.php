<?php

namespace App\ML\FileType;

class OldDataFileType implements FileTypeInterface
{
    public function getColumnsNames(): array
    {
        return [
            'original',
            'NST_level2_1',
            'NST_level2_2',
            'NST_level2_3',
            'NST_level2_4',
        ];
    }

    public function getName(): string
    {
        return 'old';
    }
}