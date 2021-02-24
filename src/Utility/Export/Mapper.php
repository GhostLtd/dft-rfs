<?php

namespace App\Utility\Export;

interface Mapper
{
    public function getData(array $sourceData);
}