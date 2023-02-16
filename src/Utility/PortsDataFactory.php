<?php

namespace App\Utility;

use App\Repository\Route\RouteRepository;

class PortsDataFactory
{
    protected RouteRepository $routeRepository;

    public function __construct(RouteRepository $routeRepository)
    {
        $this->routeRepository = $routeRepository;
    }

    public function getData(string $direction): PortsDataSet
    {
        return new PortsDataSet($this->routeRepository, $direction);
    }
}