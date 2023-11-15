<?php

namespace App\Repository;

use App\Entity\RoRoUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RoRoUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method RoRoUser[]    findAll()
 * @method RoRoUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RoRoUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RoRoUser::class);
    }

    public function find($id, $lockMode = null, $lockVersion = null): ?RoRoUser
    {
        try {
            return $this
                ->createQueryBuilder('user')
                ->select('user, operator, routes, uk_port, foreign_port')
                ->join('user.operator', 'operator')
                ->leftJoin('operator.routes', 'routes')
                ->leftJoin('routes.ukPort', 'uk_port')
                ->leftJoin('routes.foreignPort', 'foreign_port')
                ->where('user.id = :userId')
                ->setParameters([
                    'userId' => $id
                ])
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    public function findOneByOperatorIdAndUserId(string $operatorId, string $userId): ?RoRoUser
    {
        try {
            return $this
                ->createQueryBuilder('u')
                ->select('u,o')
                ->join('u.operator', 'o')
                ->where('u.id = :userId')
                ->andWhere('o.id = :operatorId')
                ->setParameters([
                    'operatorId' => $operatorId,
                    'userId' => $userId,
                ])
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException) {
            return null;
        }
    }
}
