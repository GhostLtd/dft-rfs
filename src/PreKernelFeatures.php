<?php

namespace App;

class PreKernelFeatures
{
    /**
     * These features are enabled automatically when the specified ENV vars are present
     */
    public const AUTO_FEATURE_MAP = [
        'GAE_INSTANCE' => Features::GAE_ENVIRONMENT,
    ];

    private static function detectFeatures(): array
    {
        $enabledFeatures = [];

        foreach (self::AUTO_FEATURE_MAP as $envVar => $feature) {
            if (getenv($envVar)) $enabledFeatures[] = $feature;
        }

        return $enabledFeatures;
    }

    public static function getEnabledFeatures(): array {
        static $features;

        if (empty($features)) {
            $features = self::detectFeatures();
        }

        return $features;
    }

    public static function isEnabled($feature): bool
    {
        return in_array($feature, self::getEnabledFeatures());
    }
}