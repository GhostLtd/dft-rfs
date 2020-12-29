<?php

namespace App;

class Features
{
    public const GAE_ENVIRONMENT = 'GAE_ENVIRONMENT';

    // Use IRHS consignment / stops model rather than newer actions model
    public const IRHS_CONSIGNMENTS_AND_STOPS = 'IRHS_CONSIGNMENTS_AND_STOPS';

    public const FEATURE_MAP = [
        'irhs-consignments-and-stops' => self::IRHS_CONSIGNMENTS_AND_STOPS,
    ];

    private $enabledFeatures;

    public function __construct($enableFeatures = [])
    {
        $preKernelFeatures = PreKernelFeatures::getEnabledFeatures();
        $features = array_intersect_key(self::FEATURE_MAP, array_flip($enableFeatures));
        $this->enabledFeatures = array_merge($preKernelFeatures, $features);
    }

    public function isEnabled($feature): bool
    {
        return in_array($feature, $this->enabledFeatures);
    }

    public function getEnabledFeatures(): array
    {
        return $this->enabledFeatures;
    }
}