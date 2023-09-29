<?php

namespace App\Repository\Route;

use App\DTO\RoRo\UserRoute;
use App\Entity\RoRo\Operator;
use App\Entity\RoRoUser;
use App\Entity\Route\Route;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
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
        } catch (NonUniqueResultException $e) {
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
     * @return array<UserRoute>
     */
    public function getRoutesForUser(RoRoUser $user): array
    {
        $bookmarkedRouteIds = $this->getBookmarkedRouteIds($user);

        $isOperatorRoute = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('op_route.id')
            ->from(Operator::class, 'op')
            ->join('op.routes', 'op_route')
            ->where('op_route.isActive = 1')
            ->andWhere('op.id = :operatorId')
            ->getDQL();

        $queryBuilder = $this->createQueryBuilder('routes');

        $results = $queryBuilder
            ->select(<<<EOS
routes AS route,
CASE WHEN 
    routes.id IN (:bookmarkedRouteIds) THEN 1 ELSE 0 
END AS is_bookmarked
EOS)
            ->join('routes.ukPort', 'uk_port')
            ->join('routes.foreignPort', 'foreign_port')
            ->andWhere($queryBuilder->expr()->in('routes.id', $isOperatorRoute))
            ->setParameters([
                'bookmarkedRouteIds' => $bookmarkedRouteIds,
                'operatorId' => $user->getOperator()->getId(),
            ])
            ->orderBy('uk_port.name')
            ->addOrderBy('foreign_port.name')
            ->getQuery()
            ->getResult();

        return array_map(fn(array $result) => new UserRoute($result['route'], $result['is_bookmarked']), $results);
    }

    /**
     * @return array<string>
     */
    public function getBookmarkedRouteIds(RoRoUser $user): array
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('route.id')
            ->from(RoRoUser::class, 'user')
            ->join('user.bookmarkedRoutes', 'route')
            ->where('user.id = :userId')
            ->getQuery()
            ->setParameters([
                'userId' => $user->getId(),
            ])
            ->getSingleColumnResult();
    }

    public function getRouteByPortNames(string $ukPort, string $foreignPort): ?Route
    {
        return $this->createQueryBuilder('route')
            ->join('route.ukPort', 'uk_port')
            ->join('route.foreignPort', 'foreign_port')
            ->where('uk_port.name = :uk_port_name')
            ->andWhere('foreign_port.name = :foreign_port_name')
            ->setParameters([
                'uk_port_name' => $ukPort,
                'foreign_port_name' => $foreignPort,
            ])
            ->getQuery()
            ->getOneOrNullResult();
    }
}
