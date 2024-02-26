<?php

namespace App\Repository\RoRo;

use App\Entity\RoRo\Operator;
use App\Entity\RoRo\OperatorGroup;
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
        } catch (NonUniqueResultException) {
            return null;
        }
    }

    public function findOperatorsWithNamePrefix(string $prefix): array
    {
        return $this->createQueryBuilder('operator', 'operator.id')
            ->where('operator.name like :prefix')
            ->orderBy('operator.name', 'asc')
            ->setParameter('prefix', addcslashes($prefix, '%_') . '%')
            ->getQuery()
            ->execute();
    }

    public function findOperatorGroupForOperator(Operator $operator): ?OperatorGroup
    {
        // Using DBAL since the tables are being joined in an unusual way
        // (i.e. not as per the ORM mapping)
        $conn = $this->getEntityManager()->getConnection();

        $groups = $conn->createQueryBuilder()
            ->select('og.*')
            ->from('roro_operator', 'op')
            ->join(
                'op',
                'roro_operator_group',
                'og',
                // LOWER() since Sqlite won't be case-insensitive...
                "LOWER(og.name) = LOWER(SUBSTRING(op.name, 1, LENGTH(og.name)))"
            )
            ->where('op.id = :operatorId')
            ->setParameter('operatorId', $operator->getId())
            ->executeQuery()
            ->fetchAllAssociative();

        $numGroups = count($groups);

        if ($numGroups > 1) {
            throw new NonUniqueResultException('More than one groups matched the given operator');
        }

        if ($numGroups === 0) {
            return null;
        }

        // We want to return a managed instance of OperatorGroup (i.e. that EntityManager knows about),
        // so that it doesn't cause upset if it is later used with the ORM
        return $this->getEntityManager()
            ->getRepository(OperatorGroup::class)
            ->find($groups[0]['id']);
    }
}
