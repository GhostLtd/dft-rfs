<?php

namespace App\DataCollector;

use App\Features;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

class FeaturesCollector extends DataCollector
{
    protected $features;

    public function __construct(Features $features)
    {
        $this->features = $features;
    }

    public function collect(Request $request, Response $response)
    {
        $this->data = [
            'features' => $this->features->getEnabledFeatures(),
        ];
    }

    public function getName()
    {
        return 'app.features_collector';
    }

    public function reset()
    {
        $this->data = [];
    }

    public function getFeatures(): array {
        return $this->data['features'];
    }
}