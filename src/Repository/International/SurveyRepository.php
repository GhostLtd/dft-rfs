<?php

namespace App\Repository\International;

use App\Entity\International\Company;
use App\Entity\International\Survey;
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
}
