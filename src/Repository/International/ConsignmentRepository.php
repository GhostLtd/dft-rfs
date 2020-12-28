<?php

namespace App\Repository\International;

use App\Entity\International\Consignment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Consignment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Consignment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Consignment[]    findAll()
 * @method Consignment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConsignmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Consignment::class);
    }

    public function workflowParamConverter(string $id): ?Consignment
    {
        if ($id === 'add') {
            return new Consignment();
        }

        try {
            return $this->createQueryBuilder('c')
                ->select('c,t')
                ->leftJoin('c.trip', 't')
                ->where('c.id = :id')
                ->setParameter('id', $id)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }
}
