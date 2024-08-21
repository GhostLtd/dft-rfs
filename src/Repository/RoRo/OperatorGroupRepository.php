<?php

namespace App\Repository\RoRo;

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
            ->select('g');

        // N.B. Our mysql database may be case-insensitive for string comparisons, but sqlite isn't!
        $qb->where(
            $qb->expr()->orX(
            // This one checks that the chosen name isn't a prefix of an existing group name
                $qb->expr()->eq('LOWER(:name)', 'LOWER(SUBSTRING(g.name, 1, LENGTH(:name)))'),

                // This one checks that an existing group name isn't a prefix of the chosen name
                $qb->expr()->eq('LOWER(g.name)', 'LOWER(SUBSTRING(:name, 1, LENGTH(g.name)))'),
            )
        );

        if ($excluding) {
            $excludingGroupId = $excluding->getId();

            if ($excludingGroupId) {
                $qb
                    ->andWhere('g.id != :groupId')
                    ->setParameter('groupId', $excludingGroupId);
            }
        }

        $groups = $qb
            ->setParameter('name', $name)
            ->getQuery()
            ->execute();

        return count($groups) > 0;
    }
}
