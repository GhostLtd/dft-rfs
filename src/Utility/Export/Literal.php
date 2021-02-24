<?php

namespace App\Utility\Export;

class Literal implements Mapper
{
    protected bool $data;

    public function __construct(bool $data)
    {
        $this->data = $data;
    }

    public function getData(array $sourceData)
    {
        return $this->data;
    }
}