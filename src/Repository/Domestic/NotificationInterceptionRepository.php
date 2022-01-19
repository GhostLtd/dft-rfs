<?php

namespace App\Repository\Domestic;

use App\Entity\Domestic\NotificationInterception;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method NotificationInterception|null find($id, $lockMode = null, $lockVersion = null)
 * @method NotificationInterception|null findOneBy(array $criteria, array $orderBy = null)
 * @method NotificationInterception[]    findAll()
 * @method NotificationInterception[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NotificationInterceptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NotificationInterception::class);
    }
}
