<?php

namespace App\Serializer\Normalizer\Mapper;

use Symfony\Component\PropertyAccess\PropertyAccess;

class CallbackProperty implements Mapper
{
    protected string $propertyPath;
    protected $callback;

    public function __construct(string $propertyPath, callable $callback)
    {
        $this->propertyPath = $propertyPath;
        $this->callback = $callback;
    }

    public function getData($sourceData)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        return ($this->callback)($accessor->getValue($sourceData, $this->propertyPath));
    }
}