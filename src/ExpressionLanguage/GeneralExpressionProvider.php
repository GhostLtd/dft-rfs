<?php

namespace App\ExpressionLanguage;

use App\Features;
use App\PreKernelFeatures;
use Exception;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Symfony\Component\ExpressionLanguage\SyntaxError;

class GeneralExpressionProvider implements ExpressionFunctionProviderInterface
{
    #[\Override]
    public function getFunctions(): array
    {
        return [
            new ExpressionFunction('is_empty',
                fn($str) => "is_empty(null)",
                fn($arguments, $str) => empty($str)
            ),
        ];
    }
}