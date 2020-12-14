<?php

namespace App\Repository\International;

use App\Entity\International\Consignment;
use App\Entity\International\SurveyResponse;
use App\Entity\International\Trip;
use App\Entity\International\Vehicle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
        if ($id === 'add') return new Consignment();

        try {
            $consignment = $this->createQueryBuilder('consignment')
                ->leftJoin('consignment.trip', 'trip')
                ->getQuery()
                ->getOneOrNullResult();
            if (!$consignment) throw new NotFoundHttpException();
            return $consignment;
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    // /**
    //  * @return InternationalConsignment[] Returns an array of InternationalConsignment objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('i.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?InternationalConsignment
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
