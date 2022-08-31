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
class NotificationInterceptionRepository extends ServiceEntityRepository implements NotificationInterceptionRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NotificationInterception::class);
    }

    public function findOneBySurvey(SurveyInterface $survey): ?NotificationInterception
    {
        return $this->findOneBy(['addressLine' => $survey->getInvitationAddress()->getLine1()]);
    }

    public function findByAllNames($excludeId, array $names = []): array
    {
        return [];
    }
}
