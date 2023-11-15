<?php

namespace App\Repository\RoRo;

use App\Entity\RoRo\Operator;
use App\Entity\RoRo\OperatorGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OperatorGroup>
 *
 * @method OperatorGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method OperatorGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method OperatorGroup[]    findAll()
 * @method OperatorGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OperatorGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OperatorGroup::class);
    }

    public function add(OperatorGroup $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(OperatorGroup $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function isNamePrefixAlreadyInUse(string $name, OperatorGroup $excluding = null): bool
    {
        // We need to escape % and _ because we're using LIKE, and don't want the user to be able to
        // add arbitrary wildcards to the expression.
        //
        // (Doctrine DBAL protects against SQL injection, but not wildcard injection)
        $slashedName = addcslashes($name, '%_');

        $qb = $this->createQueryBuilder('g')
            ->select('g')
            ->where('g.name LIKE :name');

        if ($excluding) {
            $excludingGroupId = $excluding->getId();

            if ($excludingGroupId) {
                $qb
                    ->andWhere('g.id != :groupId')
                    ->setParameter('groupId', $excludingGroupId);
            }
        }

        $groups = $qb
            ->setParameter('name', $slashedName . '%')
            ->getQuery()
            ->execute();

        return count($groups) > 0;
    }
}
