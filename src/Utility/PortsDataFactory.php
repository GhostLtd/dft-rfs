<?php

namespace App\Utility;

use App\Repository\Route\RouteRepository;

class PortsDataFactory
{
    public function __construct(protected RouteRepository $routeRepository)
    {
    }

    public function getData(string $direction): PortsDataSet
    {
        return new PortsDataSet($this->routeRepository, $direction);
    }
}