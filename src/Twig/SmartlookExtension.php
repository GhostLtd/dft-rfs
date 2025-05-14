<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SmartlookExtension extends AbstractExtension
{
    public function __construct(protected ?string $appSmartlookApiKey)
    {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_smartlook_api_key', $this->getSmartLookApiKey(...)),
        ];
    }

    public function getSmartLookApiKey(): ?string
    {
        return $this->appSmartlookApiKey;
    }
}