<?php

namespace App\Utility;

use Psr\Cache\CacheItemPoolInterface;
use ValueError;

class UrlSigner
{
    public function __construct(protected CacheItemPoolInterface $cache, protected string $secret, protected string $algorithm = 'ripemd128')
    {
    }

    public function sign(string $url, int $validFor = 0, ?int $overrideCurrentSecs = null, ?int $overrideCurrentNano = null): string
    {
        [$currentTimeSecs, $currentTimeNano] = $this->currentTimeSecondsAndNanoSeconds($overrideCurrentSecs, $overrideCurrentNano);

        $encodedUrl = new Url($url);

        $until = $validFor > 0 ?
            ($currentTimeSecs + $validFor).".{$currentTimeNano}" :
            "0";

        $hash = $this->calculateUrlHash($encodedUrl, $until);

        $encodedUrl->setQueryParam('_signature', $hash);
        if ($until) {
            $encodedUrl->setQueryParam('_until', $until);
        }

        return $encodedUrl->__toString();
    }

    public function isValid(string $url, ?int $overrideCurrentSecs = null, ?int $overrideCurrentNano = null): bool
    {
        [$currentTimeSecs, $currentTimeNano] = $this->currentTimeSecondsAndNanoSeconds($overrideCurrentSecs, $overrideCurrentNano);

        $signedUrl = new Url($url);

        $until = $signedUrl->getQueryParam('_until') ?? null;

        if ($until) {
            if (!preg_match('/^\d{1,12}\.\d{1,12}$/', $until)) {
                return false;
            }

            [$untilSecs, $untilNano] = array_map('intval', explode('.', $until));
        } else {
            $untilSecs = 0;
            $untilNano = 0;
        }

        $signature = $signedUrl->getQueryParam('_signature') ?? '';
        $calculatedHash = $this->calculateUrlHash($signedUrl, $until ?? 0);

        if (!hash_equals($signature, $calculatedHash)) {
            return false;
        }

        $cacheItem = $this->cache->getItem("signature-$signature");
        if ($cacheItem->isHit()) {
            // Already previously seen
            return false;
        }

        if (!$until) {
            return true;
        }

        // We can only do the replay prevention if we have some time bounds. Don't want to be memorising these
        // forever.
        $expiresAfter = max(0, ($untilSecs - $currentTimeSecs));

        $this->cache->save($cacheItem
            ->expiresAfter($expiresAfter)
            ->set(true)
        );

        return ($untilSecs > $currentTimeSecs || ($untilSecs === $currentTimeSecs && $untilNano > $currentTimeNano));
    }

    protected function calculateUrlHash(Url $url, string $until): string
    {
        // Extract just the path and query parts of the URL, additionally removing the _until and _signature params.
        // The hash is based upon what remains, and the $until argument.
        $pathAndParams = (clone $url)
            ->removeQueryParam('_signature')
            ->removeQueryParam('_until')
            ->setScheme(null)
            ->setUser(null)
            ->setPort(null)
            ->setPass(null)
            ->setHost(null)
            ->setFragment(null)
            ->__toString();

        return hash_hmac($this->algorithm, "{$until}:{$pathAndParams}", $this->secret);
    }

    public function currentTimeSecondsAndNanoSeconds(?int $overrideCurrentSecs, ?int $overrideCurrentNano): array
    {
        [$currentTimeSecs, $currentTimeNano] = hrtime();

        return [
            $overrideCurrentSecs ?? $currentTimeSecs,
            $overrideCurrentNano ?? $currentTimeNano
        ];
    }
}