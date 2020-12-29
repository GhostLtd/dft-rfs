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
    /**
     * @var Features
     */
    private $features;

    public function __construct(Features $features)
    {
        $this->features = $features;
    }

    public function getFunctions()
    {
        return [
            new ExpressionFunction('is_feature_enabled', function($str) {
                return "is_feature_enabled(${str})";
            }, function($arguments, $str) {
                try {
                    return $this->features->isEnabledSafe($str);
                } catch (Exception $e) {
                    throw new SyntaxError("Unknown feature '${str}'");
                }
            })
        ];
    }
}