<?php

namespace App\Serializer\Normalizer\Mapper;

class Literal implements Mapper
{
    public function __construct(protected $data)
    {
    }

    #[\Override]
    public function getData($sourceData)
    {
        return $this->data;
    }
}