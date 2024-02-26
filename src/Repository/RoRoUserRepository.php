<?php

namespace App\Repository;

use App\Entity\RoRoUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Parameter;
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

    #[\Override]
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
                ->setParameter('userId', $id)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException) {
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
                ->setParameters(new ArrayCollection([
                    new Parameter('operatorId', $operatorId),
                    new Parameter('userId', $userId),
                ]))
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException) {
            return null;
        }
    }
}
