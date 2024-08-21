<?php

namespace App\Twig;

use App\Utility\International\LoadingWithoutUnloadingHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class LoadingWithoutUnloadingExtension extends AbstractExtension
{
    public function __construct(protected LoadingWithoutUnloadingHelper $loadingWithoutUnloadingHelper)
    {}

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('irhs_get_loading_without_unloading', $this->loadingWithoutUnloadingHelper->getLoadingWithoutUnloadingForSurvey(...)),
        ];
    }
}
