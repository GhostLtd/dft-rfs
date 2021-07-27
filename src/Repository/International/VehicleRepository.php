<?php

namespace App\Repository\International;

use App\Entity\International\SurveyResponse;
use App\Entity\International\Vehicle;
use App\Utility\ExportHelper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
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
    protected ExportHelper $exportHelper;

    public function __construct(ManagerRegistry $registry, ExportHelper $exportHelper)
    {
        parent::__construct($registry, Vehicle::class);
        $this->exportHelper = $exportHelper;
    }

    public function registrationMarkAlreadyExists(Vehicle $vehicle)
    {
        $response = $vehicle->getSurveyResponse();
        $responseId = $response ? $response->getId() : null;
        $isCommitted = !!$vehicle->getId();

        try {
            $params = [
                'registrationMark' => $vehicle->getRegistrationMark(),
                'responseId' => $responseId,
            ];

            $qb = $this->createQueryBuilder('v')
                ->select('count(r)')
                ->leftJoin('v.surveyResponse', 'r')
                ->where('r.id = :responseId')
                ->andWhere('v.registrationMark = :registrationMark');

            if ($isCommitted) {
                $params['vehicleId'] = $vehicle->getId();
                $qb = $qb->andWhere('v.id != :vehicleId');
            }

            $count = $qb
                ->getQuery()
                ->setParameters($params)
                ->getSingleScalarResult();
        } catch (UnexpectedResultException $e) {
            // Should not be able to happen in this case
            throw new RuntimeException('Query failure', 0, $e);
        }

        return $count > 0;
    }

    public function findOneByIdAndSurveyResponse(string $id, SurveyResponse $response): ?Vehicle
    {
        try {
            return $this->createQueryBuilder('v')
                ->select('v,r,t')
                ->leftJoin('v.trips', 't')
                ->leftJoin('v.surveyResponse', 'r')
                ->where('v.id = :id')
                ->andWhere('r = :response')
                ->getQuery()
                ->setParameters([
                    'id' => $id,
                    'response' => $response,
                ])
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }
}
