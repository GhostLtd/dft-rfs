<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class BatchExtension extends AbstractExtension
{
    #[\Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('to_lists', $this->toLists(...)),
        ];
    }

    public function toLists(iterable $entries, int $numberOfLists): array
    {
        $lists = [];

        if (!is_array($entries)) {
            // Need to know how many there will be...
            $entries = iterator_to_array($entries);
        }

        $itemCount = count($entries);
        $itemsPerList = intval(ceil($itemCount / $numberOfLists));

        $list = 0;
        $count = 0;

        foreach($entries as $entry) {
            if ($count === $itemsPerList) {
                $count = 0;
                $list++;
            }

            if (!isset($lists[$list])) {
                $lists[$list] = [];
            }

            $lists[$list][] = $entry;
            $count++;
        }

        return $lists;
    }
}