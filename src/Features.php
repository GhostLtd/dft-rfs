<?php

namespace App;

use Exception;

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

    /**
     * @param $feature
     * @param bool $checkFeatureIsValid An optional safety check to ensure the feature being requested is even a defined feature
     * @return bool
     * @throws Exception
     */
    public function isEnabled($feature, $checkFeatureIsValid = false): bool
    {
        if ($checkFeatureIsValid) {
            $this->checkFeatureIsValid($feature);
        }
        return in_array($feature, $this->enabledFeatures);
    }

    /**
     * @param $feature
     * @throws Exception
     */
    private function checkFeatureIsValid($feature)
    {
        $allFeatures = array_merge(
            array_values(self::FEATURE_MAP),
            array_values(PreKernelFeatures::AUTO_FEATURE_MAP)
        );

        if (!in_array($feature, $allFeatures)) {
            throw new Exception("Unknown feature '${feature}'");
        }
    }

    public function getEnabledFeatures(): array
    {
        return $this->enabledFeatures;
    }
}