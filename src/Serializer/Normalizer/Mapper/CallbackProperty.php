<?php

namespace App\Serializer\Normalizer\Mapper;

class CallbackProperty implements Mapper
{
    protected $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function getData($sourceData)
    {
        return ($this->callback)($sourceData);
    }
}