<?php

namespace App\Utility\Export;

class Literal implements Mapper
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function getData($sourceData)
    {
        return $this->data;
    }
}