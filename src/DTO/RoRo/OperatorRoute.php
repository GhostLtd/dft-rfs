<?php

namespace App\DTO\RoRo;

class OperatorRoute
{
    public function __construct(protected string $operatorId, protected string $routeId)
    {
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