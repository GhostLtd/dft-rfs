<?php

namespace App\Utility;

use Google\Cloud\Storage\StorageObject;

interface PdfObjectInterface
{
    public function getStorageObject(): ?StorageObject;
    public function getComparator(): string;
}