<?php

namespace App\Repository\Route;

use App\Entity\RoRo\Operator;
use App\Entity\RoRoUser;
use App\Entity\Route\Route;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Route>
 *
 * @method Route|null find($id, $lockMode = null, $lockVersion = null)
 * @method Route|null findOneBy(array $criteria, array $orderBy = null)
 * @method Route[]    findAll()
 * @method Route[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RouteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Route::class);
    }

    public function findOneById(string $id): ?Route
    {
        try {
            return $this->createQueryBuilder('route')
                ->select('route, uk_port, foreign_port')
                ->join('route.ukPort', 'uk_port')
                ->join('route.foreignPort', 'foreign_port')
                ->where('route.id = :id')
                ->orderBy('uk_port.name')
                ->addOrderBy('foreign_port.name')
                ->getQuery()
                ->setParameter('id', $id)
                ->getOneOrNullResult();
        } catch (NonUniqueResultException) {
            return null;
        }
    }

    /**
     * @return array<Route>
     */
    public function findAllActiveOperatorRoutes(): array
    {
        return $this->createQueryBuilder('route')
            ->select('route, operator')
            ->join('route.roroOperators', 'operator')
            ->where('route.isActive = 1')
            ->andWhere('operator.isActive = 1')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<Route>
     */
    public function getRoutesForUser(RoRoUser $user): array
    {
        $isOperatorRoute = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('op_route.id')
            ->from(Operator::class, 'op')
            ->join('op.routes', 'op_route')
            ->where('op_route.isActive = 1')
            ->andWhere('op.id = :operatorId')
            ->getDQL();

        $queryBuilder = $this->createQueryBuilder('routes');

        return $queryBuilder
            ->select('route')
            ->from(Route::class, 'route')
            ->join('routes.ukPort', 'uk_port')
            ->join('routes.foreignPort', 'foreign_port')
            ->where($queryBuilder->expr()->in('routes.id', $isOperatorRoute))
            ->setParameter('operatorId', $user->getOperator()->getId())
            ->orderBy('uk_port.name')
            ->addOrderBy('foreign_port.name')
            ->getQuery()
            ->getResult();
    }

    public function getRouteByPortNames(string $ukPort, string $foreignPort): ?Route
    {
        return $this->createQueryBuilder('route')
            ->join('route.ukPort', 'uk_port')
            ->join('route.foreignPort', 'foreign_port')
            ->where('uk_port.name = :uk_port_name')
            ->andWhere('foreign_port.name = :foreign_port_name')
            ->setParameters(new ArrayCollection([
                new Parameter('uk_port_name', $ukPort),
                new Parameter('foreign_port_name', $foreignPort),
            ]))
            ->getQuery()
            ->getOneOrNullResult();
    }
}
