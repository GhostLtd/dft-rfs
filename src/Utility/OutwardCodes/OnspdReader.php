<?php

namespace App\Utility\OutwardCodes;

/**
 * Reads an ONS Postcode Directory (ONSPD) release — either the distributed ZIP or an
 * extracted CSV — and counts live and terminated postcodes per outward code.
 *
 * Only two ONSPD columns are used: "pcds" (the postcode, with a single space) and
 * "doterm" (date of termination, blank for live postcodes).
 */
final class OnspdReader
{
    private const string POSTCODE_COLUMN = 'pcds';
    private const string TERMINATION_COLUMN = 'doterm';
    private const string BOM = "\xEF\xBB\xBF";

    /**
     * @param callable(string $source): void|null $onSource called before each CSV file is read
     * @param callable(int $rows): void|null $onProgress called periodically while reading rows
     */
    public function read(string $path, ?callable $onSource = null, ?callable $onProgress = null): OutwardCodeCounts
    {
        if (!is_file($path)) {
            throw new \RuntimeException("File not found: {$path}");
        }

        return $this->isZip($path)
            ? $this->readZip($path, $onSource, $onProgress)
            : $this->readCsvFile($path, $onSource, $onProgress);
    }

    private function readCsvFile(string $path, ?callable $onSource, ?callable $onProgress): OutwardCodeCounts
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \RuntimeException("Could not open {$path}");
        }

        try {
            $source = basename($path);
            if ($onSource) {
                $onSource($source);
            }
            $counts = [];
            $this->aggregate($handle, $source, $counts, $onProgress);
        } finally {
            fclose($handle);
        }

        return new OutwardCodeCounts($counts, [$source]);
    }

    private function readZip(string $path, ?callable $onSource, ?callable $onProgress): OutwardCodeCounts
    {
        $zip = new \ZipArchive();
        $status = $zip->open($path, \ZipArchive::RDONLY);
        if ($status !== true) {
            throw new \RuntimeException("Could not open ZIP {$path} (ZipArchive error {$status})");
        }

        try {
            $members = $this->chooseMembers($zip, $this->candidateMembers($zip));
            if (!$members) {
                throw new \RuntimeException(sprintf(
                    'No CSV containing the "%s" and "%s" columns was found in %s',
                    self::POSTCODE_COLUMN, self::TERMINATION_COLUMN, $path,
                ));
            }

            $counts = [];
            foreach ($members as $member) {
                if ($onSource) {
                    $onSource($member);
                }
                $handle = $zip->getStream($member);
                if ($handle === false) {
                    throw new \RuntimeException("Could not read {$member} from {$path}");
                }
                try {
                    $this->aggregate($handle, $member, $counts, $onProgress);
                } finally {
                    fclose($handle);
                }
            }
        } finally {
            $zip->close();
        }

        return new OutwardCodeCounts($counts, array_map(basename(...), $members));
    }

    /**
     * @return list<array{name: string, size: int}> CSV members whose header has the columns we need
     */
    private function candidateMembers(\ZipArchive $zip): array
    {
        $candidates = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if ($stat === false || str_ends_with($stat['name'], '/') || !str_ends_with(strtolower($stat['name']), '.csv')) {
                continue;
            }

            $handle = $zip->getStream($stat['name']);
            if ($handle === false) {
                continue;
            }
            try {
                $header = $this->readHeader($handle);
            } finally {
                fclose($handle);
            }

            if ($header !== null && isset($header[self::POSTCODE_COLUMN], $header[self::TERMINATION_COLUMN])) {
                $candidates[] = ['name' => $stat['name'], 'size' => $stat['size']];
            }
        }

        return $candidates;
    }

    /**
     * Prefer the single full-UK ONSPD CSV where present (e.g. Data/ONSPD_AUG_2026_UK.csv), which avoids
     * double counting when the ZIP also holds the per-area split copies under Data/multi_csv/. Failing
     * that, read every candidate: the split copies are disjoint, so summing them is correct.
     *
     * @param list<array{name: string, size: int}> $candidates
     * @return list<string> member names
     */
    private function chooseMembers(\ZipArchive $zip, array $candidates): array
    {
        $fullUk = array_filter($candidates, function (array $c): bool {
            $base = strtolower(basename($c['name']));
            return str_contains($base, 'onspd') && str_contains($base, '_uk') && !str_contains(strtolower($c['name']), 'multi');
        });

        if ($fullUk) {
            usort($fullUk, fn(array $a, array $b): int => $b['size'] <=> $a['size']);
            return [$fullUk[0]['name']];
        }

        $names = array_column($candidates, 'name');
        sort($names, SORT_STRING);

        return $names;
    }

    /**
     * @param resource $handle
     * @return array<string, int>|null lowercased column name => index, or null if the file is empty
     */
    private function readHeader($handle): ?array
    {
        $row = fgetcsv($handle, escape: '');
        if ($row === false || $row === [null]) {
            return null;
        }

        $header = [];
        foreach ($row as $i => $name) {
            $name = strtolower(trim((string) $name));
            if ($i === 0 && str_starts_with($name, self::BOM)) {
                $name = substr($name, strlen(self::BOM));
            }
            $header[$name] = $i;
        }

        return $header;
    }

    /**
     * @param resource $handle
     * @param array<string, array{live: int, terminated: int}> $counts
     */
    private function aggregate($handle, string $source, array &$counts, ?callable $onProgress): void
    {
        $header = $this->readHeader($handle);
        if ($header === null || !isset($header[self::POSTCODE_COLUMN], $header[self::TERMINATION_COLUMN])) {
            throw new \RuntimeException(sprintf(
                '%s does not contain the expected "%s" and "%s" columns',
                $source, self::POSTCODE_COLUMN, self::TERMINATION_COLUMN,
            ));
        }
        $postcodeIdx = $header[self::POSTCODE_COLUMN];
        $terminationIdx = $header[self::TERMINATION_COLUMN];

        $rows = 0;
        while (($row = fgetcsv($handle, escape: '')) !== false) {
            if ($row === [null]) {
                continue; // blank line
            }

            $outwardCode = self::outwardCode((string) ($row[$postcodeIdx] ?? ''));
            if ($outwardCode === '') {
                continue;
            }

            $counts[$outwardCode] ??= ['live' => 0, 'terminated' => 0];
            $key = trim((string) ($row[$terminationIdx] ?? '')) === '' ? 'live' : 'terminated';
            $counts[$outwardCode][$key]++;

            if ($onProgress && ++$rows % 100000 === 0) {
                $onProgress($rows);
            }
        }
    }

    /**
     * The outward code of a postcode, e.g. "BN1 1AA" => "BN1". ONSPD's "pcds" always has a single
     * space, but a postcode with no space is handled by taking the inward code to be the final 3 chars.
     */
    public static function outwardCode(string $postcode): string
    {
        $postcode = strtoupper(trim(preg_replace('/\s+/', ' ', $postcode) ?? ''));
        if ($postcode === '') {
            return '';
        }

        $space = strpos($postcode, ' ');
        if ($space !== false) {
            return substr($postcode, 0, $space);
        }

        return strlen($postcode) > 3 ? substr($postcode, 0, -3) : $postcode;
    }

    private function isZip(string $path): bool
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return false;
        }
        $magic = fread($handle, 4);
        fclose($handle);

        return $magic === "PK\x03\x04";
    }
}
