<?php

namespace App\Repository\International;

use App\Entity\International\Action;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Action|null find($id, $lockMode = null, $lockVersion = null)
 * @method Action|null findOneBy(array $criteria, array $orderBy = null)
 * @method Action[]    findAll()
 * @method Action[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ActionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Action::class);
    }

    public function getNextNumber(string $tripId): int
    {
        $currentMax = $this->createQueryBuilder('a')
            ->select('max(a.number)')
            ->where('a.trip = :tripId')
            ->setParameter('tripId', $tripId)
            ->getQuery()
            ->getSingleScalarResult();

        return $currentMax ? ($currentMax + 1) : 1;
    }

    /**
     * @return ArrayCollection|Action[]
     */
    public function getLoadingActions(string $tripId)
    {
        return $this->createQueryBuilder('a')
            ->select('a')
            ->where('a.trip = :tripId')
            ->andWhere('a.loading = :loading')
            ->setParameters([
                'tripId' => $tripId,
                'loading' => true,
            ])
            ->orderBy('a.number', 'ASC')
            ->getQuery()
            ->execute();
    }
}
