<?php

namespace App\Security\Voter;

use App\Features;
use App\PreKernelFeatures;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class FeaturesVoter extends Voter
{
    private $enhancedFeatures;

    public function __construct(Features $enhancedFeatures)
    {
        $this->enhancedFeatures = $enhancedFeatures;
    }

    protected function supports($attribute, $subject)
    {
        return in_array($attribute, Features::FEATURE_MAP) || in_array($attribute, PreKernelFeatures::AUTO_FEATURE_MAP);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        return $this->enhancedFeatures->isEnabled($attribute);
    }
}