<?php

namespace App\Repository\International;

use App\Entity\International\NotificationInterception;
use App\Entity\International\Survey;
use App\Entity\SurveyInterface;
use App\Repository\NotificationInterceptionRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
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

    /**
     * @param Survey $survey
     * @throws NonUniqueResultException
     */
    public function findOneBySurvey(SurveyInterface $survey): ?NotificationInterception
    {
        $query = $this->createQueryBuilder('ni')
            ->leftJoin('ni.additionalNames', 'an')
            ->andWhere('ni.primaryName = :name OR an.name = :name')
            ->setParameters([
                'name' => $survey->getCompany()->getBusinessName(),
            ]);

        return $query->getQuery()->getOneOrNullResult();
    }

    public function findByAllNames($excludeId, array $names = []): array
    {
        $others = [];
        foreach ($names as $name)
        {
            $query = $this->createQueryBuilder('ni')
                ->leftJoin('ni.additionalNames', 'an')
                ->andWhere('ni.primaryName = :name OR an.name = :name')
                ->setParameter('name', $name);
            if (!is_null($excludeId)) {
                $query
                    ->andWhere('ni.id <> :exId')
                    ->setParameter('exId', $excludeId);
            }
            $result = $query->getQuery()->execute();
            if (count($result) > 0) $others[] = $name;
        }
        return array_unique($others);
    }
}
