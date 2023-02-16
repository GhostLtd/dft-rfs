<?php

namespace App\Repository\RoRo;

use App\Entity\RoRo\VehicleCount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VehicleCount>
 *
 * @method VehicleCount|null find($id, $lockMode = null, $lockVersion = null)
 * @method VehicleCount|null findOneBy(array $criteria, array $orderBy = null)
 * @method VehicleCount[]    findAll()
 * @method VehicleCount[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CountryVehicleCountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VehicleCount::class);
    }

    public function add(VehicleCount $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(VehicleCount $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
