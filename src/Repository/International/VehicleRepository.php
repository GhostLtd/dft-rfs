<?php

namespace App\Repository\International;

use App\Entity\Distance;
use App\Entity\International\SurveyResponse;
use App\Entity\International\Vehicle;
use App\Entity\Vehicle as BaseVehicle;
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

    public function queryForExport()
    {
        return $this->createQueryBuilder('v')
            ->leftJoin('v.surveyResponse', 'r')
            ->leftJoin('r.survey', 's')
            ->leftJoin('v.trips', 't')
            ->select('v.id AS id')
            // IRHVehicleDetailsID              - ??
            ->addSelect('v.registrationMark AS RegMark')
            ->addSelect('s.referenceNumber AS RefNumber')
            // FirstDate                        - ??
            // SecondDate                       - ??
            ->addSelect('v.grossWeight AS GrossVehicleWeight')
            ->addSelect('0 AS GrossVehicleWeightNotEntered')
            ->addSelect('v.carryingCapacity AS CarryingCapacity')
            ->addSelect('0 AS CarryingCapacityNotEntered')
            ->addSelect($this->exportHelper->expandConstBitMap('v.operationType', [
                'OnOwnAccount' => BaseVehicle::OPERATION_TYPE_ON_OWN_ACCOUNT,
                'ForHireReward' => BaseVehicle::OPERATION_TYPE_FOR_HIRE_AND_REWARD,
                'MainlyOperatedNotEntered' => null,
            ]))
            // DateInput                        - ??
            // DateCompleted                    - ??
            ->addSelect($this->exportHelper->expandConstBitMap('v.bodyType', [
                'FlatDropSided' => BaseVehicle::BODY_TYPE_FLAT_DROP,
                'BoxNonSpecialised' => BaseVehicle::BODY_TYPE_BOX,
                'TemperatureControlled' => BaseVehicle::BODY_TYPE_TEMPERATURE_CONTROLLED,
                'CurtainSided' => BaseVehicle::BODY_TYPE_CURTAIN_SIDED,
                'LiquidTanker' => BaseVehicle::BODY_TYPE_LIQUID,
                'SolidBulkTanker' => BaseVehicle::BODY_TYPE_SOLID_BULK,
                'LiveStockCarrier' => BaseVehicle::BODY_TYPE_LIVESTOCK,
                'CarTansporter' => BaseVehicle::BODY_TYPE_CAR,
                'Tipper' => BaseVehicle::BODY_TYPE_TIPPER,
                'Other' => BaseVehicle::BODY_TYPE_OTHER,
                'TrailerTypeNotEntered' => null,
            ]))
            // QuestionaireType                 - ??
            // LastAddressLineDesc              - ??
            ->addSelect('3 AS SurveyType')
            // TripDataLoaded                   - ??
            ->addSelect('t.outboundUkPort AS UKPortOfDeparture')
            ->addSelect('t.outboundForeignPort AS ForeignPortOfArrival')
            ->addSelect($this->exportHelper->expandConstBitMap('t.outboundWasEmpty', [
                'OutwardVehicleEmpty' => true,
            ]))
            ->addSelect('t.returnUkPort AS UKPortOfArrival')
            ->addSelect('t.returnForeignPort AS ForeignPortOfDeparture')
            ->addSelect($this->exportHelper->expandConstBitMap('t.returnWasEmpty', [
                'ReturnVehicleEmpty' => true,
            ]))
            // N.B. CountriesTransittedOther will need processing/merging into CountriesTransitted
            // TODO: This blows up as it returns an array which the template can't deal with
//            ->addSelect('t.countriesTransitted AS CountriesTransitted')
//            ->addSelect('t.countriesTransittedOther AS CountriesTransittedOther')
            ->addSelect('t.roundTripDistance.value AS TotalRoundTripDistance')
            ->addSelect($this->exportHelper->expandConstBitMap('t.roundTripDistance.unit', [
                'KMsYN' => Distance::UNIT_KILOMETERS,
                'MilesYN' => Distance::UNIT_MILES,
            ]))
            ->addSelect('0 AS DatesNotEntered')
            ->addSelect('0 AS OutwardJourneyNotEntered')
            ->addSelect('0 AS ReturnJouneyNotEntered')
            ->addSelect('0 AS TripInfoNotEntered')
            // SurveyStartDate                  - ??
            // RecordLastUpdatedBy              - ??
            // AdjustedGrossVehicleWeight       - ??
            // AdjustedCarryingCapacity         - ??
            ->addSelect('r.businessNature AS BusinessType')
            ->getQuery()
            ->execute();
    }
}
