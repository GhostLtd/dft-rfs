<?php

namespace App\Utility;

use App\Repository\International\CrossingRouteRepository;

class PortsDataFactory
{
    protected CrossingRouteRepository $crossingRouteRepository;

    public function __construct(CrossingRouteRepository $crossingRouteRepository)
    {
        $this->crossingRouteRepository = $crossingRouteRepository;
    }

    public function getData(string $direction): PortsDataSet
    {
        return new PortsDataSet($this->crossingRouteRepository, $direction);
    }
}