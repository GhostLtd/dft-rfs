<?php

namespace App\Utility\Export;

use Symfony\Component\PropertyAccess\PropertyAccess;

class Property implements Mapper
{
    protected string $propertyPath;

    public function __construct(string $propertyPath)
    {
        $this->propertyPath = $propertyPath;
    }

    public function getData($sourceData)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        return $accessor->getValue($sourceData, $this->propertyPath);
    }
}