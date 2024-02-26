<?php


namespace App\Serializer\Normalizer;


use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class Utils
{
    protected static PropertyAccessor $propertyAccessor;

    public static function formatBool(?bool $property): ?int
    {
        if (is_null($property)) return null;
        return $property ? 1 : 0;
    }

    public static function formatFloat(?int $property, $places = 2): ?string
    {
        if (is_null($property)) return null;
        return number_format($property / 10 ** $places, $places, '.', '');
    }

    public static function getNullOrProperty($object, string $property)
    {
        if (is_null($object)) return null;
        return self::getPropertyAccessor()->getValue($object, $property);
    }

    public static function getPropertyAccessor(): PropertyAccessor
    {
        if (!isset(self::$propertyAccessor)) {
            self::$propertyAccessor = PropertyAccess::createPropertyAccessor();
        }
        return self::$propertyAccessor;
    }
}