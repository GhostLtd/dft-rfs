<?php

namespace App\Repository\International;

use App\Entity\International\Vehicle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\UnexpectedResultException;
use Doctrine\Persistence\ManagerRegistry;
use RuntimeException;

/**
 * @method Vehicle|null find($id, $lockMode = null, $lockVersion = null)
 * @method Vehicle|null findOneBy(array $criteria, array $orderBy = null)
 * @method Vehicle[]    findAll()
 * @method Vehicle[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VehicleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vehicle::class);
    }

    public function alreadyExists(?string $registrationMark, ?int $responseId)
    {
        try {
            $count = $this->createQueryBuilder('v')
                ->select('count(r)')
                ->leftJoin('v.surveyResponse', 'r')
                ->where('r.id = :responseId')
                ->andWhere('v.registrationMark = :registrationMark')
                ->getQuery()
                ->setParameters([
                    'registrationMark' => $registrationMark,
                    'responseId' => $responseId,
                ])
                ->getSingleScalarResult();
        } catch (UnexpectedResultException $e) {
            // Should not be able to happen in this case
            throw new RuntimeException('Query failure', 0, $e);
        }

        return $count > 0;
    }

    // /**
    //  * @return InternationalVehicle[] Returns an array of InternationalVehicle objects
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
    public function findOneBySomeField($value): ?InternationalVehicle
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
