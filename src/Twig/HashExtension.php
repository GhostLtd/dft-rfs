<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class HashExtension extends AbstractExtension
{
    #[\Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('hash', $this->hash(...)),
        ];
    }

    public function hash(string $string, string $algorithm = 'md5'): string
    {
        return hash($algorithm, $string);
    }
}