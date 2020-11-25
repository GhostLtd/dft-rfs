<?php

namespace App\Repository\International;

use App\Entity\International\Company;
use App\Entity\International\SamplingGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Company|null find($id, $lockMode = null, $lockVersion = null)
 * @method Company|null findOneBy(array $criteria, array $orderBy = null)
 * @method Company[]    findAll()
 * @method Company[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CompanyRepository extends ServiceEntityRepository
{
    protected $samplingGroupRepo;

    protected $manager;

    public function __construct(ManagerRegistry $registry)
    {
        $this->samplingGroupRepo = $registry->getRepository(SamplingGroup::class);
        $this->manager = $registry->getManager();

        parent::__construct($registry, Company::class);
    }

    public function fetchOrCreateTestCompany()
    {
        $companyName = 'Test sprockets inc';
        $company = $this->findOneBy(['businessName' => $companyName]);

        if (!$company) {
            /** @var SamplingGroup $samplingGroup */
            $samplingGroup = $this->samplingGroupRepo->findOneBy(['number' => 1, 'sizeGroup' => 1]);

            $company = (new Company())
                ->setBusinessName($companyName)
                ->setSamplingGroup($samplingGroup);

            $this->manager->persist($company);
            $this->manager->flush();
        }

        return $company;
    }

    // /**
    //  * @return InternationalCompany[] Returns an array of InternationalCompany objects
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
    public function findOneBySomeField($value): ?InternationalCompany
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
