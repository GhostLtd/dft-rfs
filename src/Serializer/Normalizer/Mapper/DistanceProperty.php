<?php

namespace App\Serializer\Normalizer\Mapper;

use App\Entity\Distance;
use Symfony\Component\PropertyAccess\PropertyAccess;

class DistanceProperty implements Mapper
{
    public function __construct(protected string $propertyPath, private ?string $inUnits = null, private $defaultValue = null)
    {
    }

    #[\Override]
    public function getData($sourceData)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        try {
            /* @var Distance $distance */
            $distance = $accessor->getValue($sourceData, "{$this->propertyPath}");
            return $distance->getValueNormalized($this->inUnits) ?? 0;
        } catch (\Throwable) {
            return $this->defaultValue;
        }
    }
}