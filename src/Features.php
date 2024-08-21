<?php

namespace App;

class Features
{
    public const DEV_RORO_AUTO_LOGIN = 'DEV_RORO_AUTO_LOGIN';
    public const GAE_ENVIRONMENT = 'GAE_ENVIRONMENT';
    public const SMARTLOOK_USER_SESSION_RECORDING = 'SMARTLOOK_USER_SESSION_RECORDING';

    public const FEATURE_MAP = [
        'dev-roro-auto-login' => self::DEV_RORO_AUTO_LOGIN,
        'smartlook-user-session-recording' => self::SMARTLOOK_USER_SESSION_RECORDING,
    ];

    private array $enabledFeatures;

    public function __construct($enableFeatures = [])
    {
        $preKernelFeatures = PreKernelFeatures::getEnabledFeatures();
        $features = array_intersect_key(self::FEATURE_MAP, array_flip($enableFeatures));
        $this->enabledFeatures = array_merge($preKernelFeatures, $features);
    }

    /**
     * @param $feature
     * @param bool $checkFeatureIsValid An optional safety check to ensure the feature being requested is even a defined feature
     * @return bool
     */
    public function isEnabled($feature, bool $checkFeatureIsValid = false): bool
    {
        if ($checkFeatureIsValid) {
            $this->checkFeatureIsValid($feature);
        }
        return in_array($feature, $this->enabledFeatures);
    }

    /**
     * @param $feature
     */
    private function checkFeatureIsValid($feature): void
    {
        $allFeatures = array_merge(
            array_values(self::FEATURE_MAP),
            array_values(PreKernelFeatures::AUTO_FEATURE_MAP)
        );

        if (!in_array($feature, $allFeatures)) {
            throw new \RuntimeException("Unknown feature '{$feature}'");
        }
    }

    public function getEnabledFeatures(): array
    {
        return $this->enabledFeatures;
    }
}