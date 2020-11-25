<?php

namespace App\Repository\International;

use App\Entity\International\Company;
use App\Entity\International\Survey;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Survey|null find($id, $lockMode = null, $lockVersion = null)
 * @method Survey|null findOneBy(array $criteria, array $orderBy = null)
 * @method Survey[]    findAll()
 * @method Survey[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SurveyRepository extends ServiceEntityRepository
{
    protected $entityManager;

    /** @var CompanyRepository $companyRepo */
    protected $companyRepo;

    public function __construct(ManagerRegistry $registry)
    {
        $this->entityManager = $registry->getManager();
        $this->companyRepo = $this->entityManager->getRepository(Company::class);

        parent::__construct($registry, Survey::class);
    }

    public function fetchOrCreateTestSurvey(): Survey
    {
        /** @var Survey|null $survey */
        $survey = $this->createQueryBuilder('s')
            ->select('s, r, v')
            ->leftJoin('s.response', 'r')
            ->leftJoin('r.vehicles', 'v')
            ->orderBy('s.id', 'DESC')
            ->getQuery()
            ->getOneOrNullResult();

        if (!$survey) {
            $company = $this->companyRepo->fetchOrCreateTestCompany();

            $dispatchDate = new DateTime();
            $dueDate = (clone $dispatchDate)->modify('+4 weeks');

            $survey = (new Survey())
                ->setCompany($company)
                ->setDispatchDate($dispatchDate)
                ->setDueDate($dueDate)
                ->setReferenceNumber('test1234')
                ->setPasscode('1234567898765432');

            $this->entityManager->persist($survey);
            $this->entityManager->flush();
        }

        return $survey;
    }


    // /**
    //  * @return InternationalSurvey[] Returns an array of InternationalSurvey objects
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
    public function findOneBySomeField($value): ?InternationalSurvey
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
