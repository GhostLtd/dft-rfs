<?php

namespace App\Serializer\Normalizer\Mapper;

use App\Entity\Distance;
use Symfony\Component\PropertyAccess\PropertyAccess;

class DistanceProperty implements Mapper
{
    protected string $propertyPath;
    private $defaultValue;
    private $inUnits;

    public function __construct(string $propertyPath, string $inUnits = null, $defaultValue = null)
    {
        $this->propertyPath = $propertyPath;
        $this->defaultValue = $defaultValue;
        $this->inUnits = $inUnits;
    }

    public function getData($sourceData)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        try {
            /* @var Distance $distance */
            $distance = $accessor->getValue($sourceData, "{$this->propertyPath}");
            return $distance->getValueNormalized($this->inUnits) ?? 0;
        } catch (\Throwable $e) {
            return $this->defaultValue;
        }
    }
}