<?php

namespace App\ExpressionLanguage;

use App\Features;
use App\PreKernelFeatures;
use Exception;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Symfony\Component\ExpressionLanguage\SyntaxError;

class FeatureExpressionProvider implements ExpressionFunctionProviderInterface
{
    public function __construct(private Features $features)
    {
    }

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new ExpressionFunction('is_feature_enabled',
                fn($str) => "is_feature_enabled({$str})",
                function($arguments, $str) {
                    try {
                        return $this->features->isEnabled($str, true);
                    } catch (Exception) {
                        throw new SyntaxError("Unknown feature '{$str}'");
                    }
                })
        ];
    }
}