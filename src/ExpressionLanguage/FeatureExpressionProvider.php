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
    public function getFunctions()
    {
        return [
            new ExpressionFunction('feature', function($str) {
                return "feature(${str})";
            }, function($arguments, $str) {
                try {
                    return self::feature($str);
                } catch (Exception $e) {
                    throw new SyntaxError("Unknown feature '${str}'");
                }
            })
        ];
    }

    public static function feature($str) {
        {
            $allFeatures = array_merge(
                array_values(Features::FEATURE_MAP),
                array_values(PreKernelFeatures::AUTO_FEATURE_MAP)
            );

            if (!in_array($str, $allFeatures)) {
                throw new Exception("Unknown feature '${str}'");
            }

            return $str;
        }
    }
}