<?php


namespace App\Utility;


class TraitUtils
{
    public static function classUsesTrait($class, string $trait, bool $autoload = true): bool {
        $traits = self::classUsesRecursive($class, $autoload);
        return in_array($trait, $traits);
    }

    public static function classUsesRecursive($class, bool $autoload = true): array {
        $traits = [];

        do {
            $traits = array_merge($traits, class_uses($class, $autoload));
        } while($class = get_parent_class($class));

        foreach($traits as $trait) {
            $traits = array_merge(class_uses($trait, $autoload), $traits);
        }

        return array_unique($traits);
    }
}