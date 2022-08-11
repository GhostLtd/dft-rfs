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
    private Features $features;

    public function __construct(Features $features)
    {
        $this->features = $features;
    }

    public function getFunctions(): array
    {
        return [
            new ExpressionFunction('is_feature_enabled', function($str) {
                return "is_feature_enabled(${str})";
            }, function($arguments, $str) {
                try {
                    return $this->features->isEnabled($str, true);
                } catch (Exception $e) {
                    throw new SyntaxError("Unknown feature '${str}'");
                }
            })
        ];
    }
}