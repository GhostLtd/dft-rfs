<?php

namespace App\Utility\OutwardCodes;

final readonly class OutwardCodeCounts
{
    /**
     * @param array<string, array{live: int, terminated: int}> $counts postcode counts keyed by outward code
     * @param list<string> $sources names of the CSV files that were read
     */
    public function __construct(
        public array $counts,
        public array $sources,
    ) {
    }

    /**
     * @return list<string> outward codes with at least one live (non-terminated) postcode
     */
    public function liveOutwardCodes(): array
    {
        return array_keys(array_filter($this->counts, fn(array $c): bool => $c['live'] > 0));
    }

    /**
     * @return list<string> outward codes whose postcodes have all been terminated
     */
    public function terminatedOutwardCodes(): array
    {
        return array_keys(array_filter($this->counts, fn(array $c): bool => $c['live'] === 0));
    }

    /**
     * @return list<string>
     */
    public function allOutwardCodes(): array
    {
        return array_keys($this->counts);
    }
}
