<?php

namespace App\Utility;

use App\Entity\Route\Route;
use App\Form\InternationalSurvey\Trip\AbstractPortsAndCargoStateType;
use App\Repository\Route\RouteRepository;

class PortsDataSet
{
    protected array $ports;

    public function __construct(protected RouteRepository $routeRepository, protected string $direction)
    {
    }

    public function getDirection(): string
    {
        return $this->direction;
    }

    public function getPorts(): array
    {
        if (!isset($this->ports)) {
            $orderField = $this->direction === AbstractPortsAndCargoStateType::DIRECTION_OUTBOUND ?
                'ukPort.name' :
                'foreignPort.name';

            $this->ports = $this->routeRepository
                ->createQueryBuilder('route')
                ->select('route, ukPort, foreignPort')
                ->join('route.ukPort', 'ukPort')
                ->join('route.foreignPort', 'foreignPort')
                ->where('route.isActive = 1')
                ->orderBy($orderField, 'ASC')
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
                fn(Route $r) => $this->direction === AbstractPortsAndCargoStateType::DIRECTION_OUTBOUND ?
                    "{$r->getUkPort()->getName()} – {$r->getForeignPort()->getName()}" :
                    "{$r->getForeignPort()->getName()} – {$r->getUkPort()->getName()}",
                $ports
            ),
            array_map(fn(Route $r) => $r->getId(), $ports)
        );

        return ['' => null] + $choices;
    }
}