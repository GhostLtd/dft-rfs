<?php

namespace App\Utility\RoRo;

use App\Entity\Route\ForeignPort;
use App\Entity\Route\PortInterface;
use App\Entity\Route\Route;
use App\Entity\Route\UkPort;
use App\Repository\RoRo\SurveyRepository;
use App\Repository\Route\ForeignPortRepository;
use App\Repository\Route\RouteRepository;
use App\Repository\Route\UkPortRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;

class PortAndRouteUsageHelper
{
    protected array $portUsageInSurveyCounts;
    protected array $portUsageInRouteCounts;
    protected array $routeUsageCounts;

    public function __construct(protected ForeignPortRepository $foreignPortRepository, protected RouteRepository $routeRepository, protected SurveyRepository $surveyRepository, protected UkPortRepository $ukPortRepository)
    {
        $this->portUsageInSurveyCounts = [];
        $this->portUsageInRouteCounts = [];
        $this->routeUsageCounts = [];
    }

    public function isPortInUseBySurveyOrRoute(PortInterface $port): bool
    {
        return $this->isPortInUseBySurvey($port) || $this->isPortInUseByRoute($port);
    }

    public function isPortInUseByRoute(PortInterface $port): bool
    {
        $id = $port->getId();
        $count = $this->portUsageInRouteCounts[$id] ?? null;

        if ($count === null) {
            $portJoin = $this->getJoinForPortClass($port::class);

            $count = $this->routeRepository
                ->createQueryBuilder('r')
                ->select('COUNT(r) AS route_count')
                ->join($portJoin, 'p')
                ->where('p.id = :portId')
                ->setParameter('portId', $port->getId())
                ->getQuery()
                ->getSingleScalarResult();

            $this->portUsageInRouteCounts[$id] = $count;
        }

        return $count > 0;
    }

    public function isPortInUseBySurvey(PortInterface $port): bool
    {
        $id = $port->getId();
        $count = $this->portUsageInSurveyCounts[$id] ?? null;

        if ($count === null) {
            $portJoin = $this->getJoinForPortClass($port::class);

            $count = $this->getBaseUsageQueryBuilder()
                ->join($portJoin, 'p')
                ->where('p.id = :portId')
                ->setParameter('portId', $id)
                ->getQuery()
                ->getSingleScalarResult();

            $this->portUsageInSurveyCounts[$id] = $count;
        }

        return $count > 0;
    }

    public function preFetchCountsForPorts(array $ports): self
    {
        foreach([UkPort::class, ForeignPort::class] as $portClass) {
            $filteredPorts = array_filter($ports, fn(PortInterface $port) => $port instanceof $portClass);

            if (empty($filteredPorts)) {
                continue;
            }

            $ids = array_map(fn(PortInterface $port) => $port->getId(), $filteredPorts);

            $surveyCounts = $this->getRepositoryForPortClass($portClass)
                ->createQueryBuilder('p')
                ->select('p.id, COUNT(s) AS survey_count')
                ->leftJoin('p.routes', 'r')
                ->leftJoin('r.surveys', 's')
                ->where('p.id IN (:ids)')
                ->groupBy('p.id')
                ->setParameter('ids', $ids)
                ->getQuery()
                ->getArrayResult();

            foreach($surveyCounts as ['id' => $id, 'survey_count' => $surveyCount]) {
                $this->portUsageInSurveyCounts[$id] = $surveyCount;
            }

            $routeCounts = $this->getRepositoryForPortClass($portClass)
                ->createQueryBuilder('p')
                ->select('p.id, COUNT(r) AS route_count')
                ->leftJoin('p.routes', 'r')
                ->where('p.id IN (:ids)')
                ->groupBy('p.id')
                ->setParameter('ids', $ids)
                ->getQuery()
                ->getArrayResult();

            foreach($routeCounts as ['id' => $id, 'route_count' => $routeCount]) {
                $this->portUsageInRouteCounts[$id] = $routeCount;
            }
        }

        return $this;
    }

    public function isRouteInUseBySurvey(Route $route): bool
    {
        $id = $route->getId();
        $count = $this->routeUsageCounts[$id] ?? null;

        if ($count === null) {
            $count = $this->getBaseUsageQueryBuilder()
                ->where('r.id = :routeId')
                ->setParameter('routeId', $id)
                ->getQuery()
                ->getSingleScalarResult();

            $this->routeUsageCounts[$id] = $count;
        }

        return $count > 0;
    }

    public function preFetchCountsForRoutes(array $routes): self
    {
        $ids = array_map(fn(Route $route) => $route->getId(), $routes);

        $counts = $this->routeRepository
            ->createQueryBuilder('r')
            ->select('r.id, COUNT(s) AS survey_count')
            ->leftJoin('r.surveys', 's')
            ->where('r.id IN (:ids)')
            ->groupBy('r.id')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getArrayResult();

        foreach($counts as ['id' => $id, 'survey_count' => $surveyCount]) {
            $this->routeUsageCounts[$id] = $surveyCount;
        }

        return $this;
    }

    protected function getBaseUsageQueryBuilder(): QueryBuilder
    {
        return $this->surveyRepository
            ->createQueryBuilder('s')
            ->select('COUNT(s)')
            ->join('s.route', 'r');
    }

    protected function getJoinForPortClass(string $class): string
    {
        return match ($class) {
            UkPort::class => 'r.ukPort',
            ForeignPort::class => 'r.foreignPort',
        };
    }

    protected function getRepositoryForPortClass(string $class): ServiceEntityRepository
    {
        return match ($class) {
            UkPort::class => $this->ukPortRepository,
            ForeignPort::class => $this->foreignPortRepository,
        };
    }
}