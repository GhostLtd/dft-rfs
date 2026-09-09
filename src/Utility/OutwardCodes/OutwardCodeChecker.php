<?php

namespace App\Utility\OutwardCodes;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

class OutwardCodeChecker
{
    public const string DATA_FILE = 'data/uk-outward-codes.txt';

    private const string RANGE_RE = '/^([A-Z]{1,2})(\d{1,2})-(\d{1,2})$/';

    /**
     * Splits a postcode into its outward part and optional inward part. Deliberately looser than
     * PostcodeHelper's regex so that the data file, not the pattern, decides what is valid: in
     * particular the non-standard "GIR 0AA" outward code has no digit, which that regex requires.
     */
    private const string POSTCODE_SHAPE_RE = '/^([A-Z0-9]{2,4})\s*(\d[A-Z]{2})?$/i';

    /** @var array<string, true>|null */
    private ?array $outwardCodes = null;

    public function __construct(
        #[Autowire('%kernel.project_dir%/' . self::DATA_FILE)] private readonly string $dataFile,
    ) {
    }

    /**
     * Whether the outward part of the given postcode is a known outward code. Accepts a full postcode
     * ("BN1 1AA", "bn11aa") or just the outward code ("BN1"). Anything that isn't shaped like a postcode
     * returns false.
     */
    public function isValidPostcode(string $postcode): bool
    {
        if (!preg_match(self::POSTCODE_SHAPE_RE, trim($postcode), $matches)) {
            return false;
        }

        return $this->isValidOutwardCode($matches[1]);
    }

    public function isValidOutwardCode(string $outwardCode): bool
    {
        return isset($this->getOutwardCodes()[strtoupper(trim($outwardCode))]);
    }

    /**
     * @return array<string, true>
     */
    private function getOutwardCodes(): array
    {
        return $this->outwardCodes ??= $this->load();
    }

    /**
     * @return array<string, true>
     */
    private function load(): array
    {
        if (!is_file($this->dataFile)) {
            throw new \RuntimeException("Outward code data file not found: {$this->dataFile} (run rfs:dev:build-outward-codes to generate it)");
        }

        $codes = [];
        foreach (file($this->dataFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $lineNumber => $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            foreach (explode(',', $line) as $token) {
                $token = strtoupper(trim($token));
                if ($token === '') {
                    continue;
                }

                if (preg_match(self::RANGE_RE, $token, $m)) {
                    [, $area, $start, $end] = $m;
                    if ((int) $start > (int) $end) {
                        throw new \RuntimeException(sprintf('Invalid range "%s" on line %d of %s', $token, $lineNumber + 1, $this->dataFile));
                    }
                    for ($n = (int) $start; $n <= (int) $end; $n++) {
                        $codes["{$area}{$n}"] = true;
                    }
                } elseif (str_contains($token, '-')) {
                    throw new \RuntimeException(sprintf('Invalid range "%s" on line %d of %s', $token, $lineNumber + 1, $this->dataFile));
                } else {
                    $codes[$token] = true;
                }
            }
        }

        return $codes;
    }
}
