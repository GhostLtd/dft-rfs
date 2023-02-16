<?php

namespace App\DTO\RoRo;

class OperatorRoute
{
    protected string $operatorId;
    protected string $routeId;

    public function __construct(string $operatorId, string $routeId)
    {
        $this->operatorId = $operatorId;
        $this->routeId = $routeId;
    }

    public function getOperatorId(): string
    {
        return $this->operatorId;
    }

    public function getRouteId(): string
    {
        return $this->routeId;
    }
}