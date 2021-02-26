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
            $unit = $accessor->getValue($sourceData, "{$this->propertyPath}.unit");
            $value = $accessor->getValue($sourceData, "{$this->propertyPath}.value");
        } catch (\Throwable $e) {
            return $this->defaultValue;
        }

        if (is_null($this->inUnits) || $unit === $this->inUnits) {
            return $value;
        }
        switch ($this->inUnits) {
            case Distance::UNIT_KILOMETERS :
                return $value * Distance::UNIT_CONVERSION_FACTOR;
            case Distance::UNIT_MILES :
                return $value / Distance::UNIT_CONVERSION_FACTOR;
            default:
                throw new \InvalidArgumentException('Unexpected unit type');
        }
    }
}