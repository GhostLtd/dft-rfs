<?php

namespace App\Utility;

use App\Entity\International\CrossingRoute;
use App\Form\InternationalSurvey\Trip\AbstractPortsAndCargoStateType;
use App\Repository\International\CrossingRouteRepository;

class PortsDataSet
{
    protected CrossingRouteRepository $crossingRouteRepository;

    protected string $direction;

    protected array $ports;

    public function __construct(CrossingRouteRepository $crossingRouteRepository, string $direction)
    {
        $this->crossingRouteRepository = $crossingRouteRepository;
        $this->direction = $direction;
    }

    public function getDirection(): string
    {
        return $this->direction;
    }

    public function getPorts(): array
    {
        if (!isset($this->ports)) {
            $this->ports =
                $this->crossingRouteRepository
                    ->createQueryBuilder('c')
                    ->orderBy('c.' . ($this->direction === AbstractPortsAndCargoStateType::DIRECTION_OUTBOUND ? 'ukPort' : 'foreignPort'), 'ASC')
                    ->getQuery()
                    ->execute();
        }

        return $this->ports;
    }

    public function getPortChoices(): array
    {
        $ports = $this->getPorts();
        $choices = array_combine(
            array_map(
                fn(CrossingRoute $c) => $this->direction === AbstractPortsAndCargoStateType::DIRECTION_OUTBOUND ?
                    "{$c->getUkPort()} – {$c->getForeignPort()}" :
                    "{$c->getForeignPort()} – {$c->getUkPort()}",
                $ports
            ),
            array_map(fn(CrossingRoute $c) => $c->getId(), $ports)
        );

        return ['' => null] + $choices;
    }
}