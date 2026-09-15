<?php

namespace App\Tests\Utility\Reminder;

trait ResolvesFixtureRefsTrait
{
    /**
     * @param class-string $class
     * @param list<object> $entities
     * @return list<string> reference names of $entities, sorted
     */
    private function refsOf(string $class, array $entities): array
    {
        $ids = array_map(fn(object $e) => $e->getId(), $entities);
        $names = [];
        foreach ($this->refs->getIdentitiesByClass()[$class] ?? [] as $ref => $identity) {
            // getIdentitiesByClass() returns the identifier as the Uid object the custom generator produced;
            // the entity's getId() (IdTrait) returns it as a plain string.
            if (in_array((string) ($identity['id'] ?? $identity), $ids, true)) {
                $names[] = $ref;
            }
        }
        sort($names);
        return $names;
    }
}
