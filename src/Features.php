<?php

namespace App;

class Features
{
    public const GAE_ENVIRONMENT = 'GAE_ENVIRONMENT';

    /**
     * These features are enabled automatically when the specified ENV vars are present
     */
    private const AUTO_FEATURE_MAP = [
        'GAE_INSTANCE' => self::GAE_ENVIRONMENT,
    ];

//    /**
//     * These features are enabled when present in the APP_FEATURES ENV var
//     */
//    private const CONFIG_FEATURE_MAP = [
//        'gae-environment' => self::GAE_ENVIRONMENT,
//    ];


    private static function detectFeatures()
    {
        $enabledFeatures = [];

//        if ($config = getenv('APP_FEATURES')) {
//            $configFeatures = str_getcsv($config);
//            $enabledFeatures = array_intersect_key(self::CONFIG_FEATURE_MAP, array_flip($configFeatures));
//        }

        foreach (self::AUTO_FEATURE_MAP as $envVar => $feature) {
            if (getenv($envVar)) $enabledFeatures[] = $feature;
        }

        return $enabledFeatures;
    }

    public static function isEnabled($feature)
    {
        static $features;
        if (empty($features)) $features = self::detectFeatures();
        return in_array($feature, $features);
    }
}