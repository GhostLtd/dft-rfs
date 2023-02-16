<?php

namespace App\Repository\RoRo;

use App\Entity\RoRo\Operator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Operator>
 *
 * @method Operator|null find($id, $lockMode = null, $lockVersion = null)
 * @method Operator|null findOneBy(array $criteria, array $orderBy = null)
 * @method Operator[]    findAll()
 * @method Operator[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OperatorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Operator::class);
    }

    public function findOneById(string $id): ?Operator
    {
        try {
            return $this->createQueryBuilder('operator')
                ->select('operator, routes, uk_port, foreign_port')
                ->leftJoin('operator.routes', 'routes')
                ->leftJoin('routes.ukPort', 'uk_port')
                ->leftJoin('routes.foreignPort', 'foreign_port')
                ->where('operator.id = :id')
                ->orderBy('uk_port.name')
                ->addOrderBy('foreign_port.name')
                ->getQuery()
                ->setParameter('id', $id)
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }
}
