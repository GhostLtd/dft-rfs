<?php

namespace App\Serializer\Normalizer\Mapper;

use Symfony\Component\PropertyAccess\PropertyAccess;

class CallbackProperty implements Mapper
{
    protected $callback;

    public function __construct(protected string $propertyPath, callable $callback)
    {
        $this->callback = $callback;
    }

    #[\Override]
    public function getData($sourceData)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        return ($this->callback)($accessor->getValue($sourceData, $this->propertyPath));
    }
}