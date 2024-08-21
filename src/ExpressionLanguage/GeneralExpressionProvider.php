<?php

namespace App\ExpressionLanguage;

use App\Features;
use App\PreKernelFeatures;
use App\Utility\International\LoadingWithoutUnloadingHelper;
use Exception;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Symfony\Component\ExpressionLanguage\SyntaxError;

class GeneralExpressionProvider implements ExpressionFunctionProviderInterface
{
    public function __construct(protected LoadingWithoutUnloadingHelper $loadingWithoutUnloadingHelper)
    {}

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new ExpressionFunction('is_empty',
                fn($str) => "is_empty({$str})",
                fn($arguments, $str) => empty($str)
            ),
            new ExpressionFunction('irhs_has_loading_without_unloading',
                fn($str) => "irhs_has_loading_without_unloading({$str})",
                fn($arguments, $target) => $this->loadingWithoutUnloadingHelper->hasLoadingWithoutUnloading($target),
            )
        ];
    }
}
