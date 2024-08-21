<?php

namespace App\DataCollector;

use App\Features;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

class FeaturesCollector extends DataCollector
{
    public function __construct(protected Features $features)
    {
    }

    #[\Override]
    public function collect(Request $request, Response $response, \Throwable $exception = null): void
    {
        $this->data = [
            'features' => $this->features->getEnabledFeatures(),
        ];
    }

    #[\Override]
    public function getName(): string
    {
        return 'app.features_collector';
    }

    #[\Override]
    public function reset(): void
    {
        $this->data = [];
    }

    public function getFeatures(): array {
        return $this->data['features'];
    }
}