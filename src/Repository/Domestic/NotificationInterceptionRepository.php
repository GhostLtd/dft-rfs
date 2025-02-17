<?php

namespace App\Repository\Domestic;

use App\Entity\Domestic\NotificationInterception;
use App\Entity\SurveyInterface;
use App\Repository\NotificationInterceptionRepositoryInterface;
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

    public function findOneBySurvey(SurveyInterface $survey): ?NotificationInterception
    {
        $query = $this->createQueryBuilder('ni')
            ->leftJoin('ni.names', 'an')
            ->andWhere('an.name = :name')
            ->setParameter('name', $survey->getInvitationAddress()->getLine1());

        return $query->getQuery()->getOneOrNullResult();
    }
}
