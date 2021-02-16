<?php

namespace App\Repository\Domestic;

use App\Entity\Domestic\Survey;
use App\Entity\Vehicle;
use App\Entity\Volume;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Survey|null find($id, $lockMode = null, $lockVersion = null)
 * @method Survey|null findOneBy(array $criteria, array $orderBy = null)
 * @method Survey[]    findAll()
 * @method Survey[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SurveyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Survey::class);
    }

    /**
     * @param $id
     * @return Survey
     */
    public function findOneByIdWithResponseAndVehicle($id)
    {
        return $this->createQueryBuilder('survey')
            ->select('survey, passcode_user, response, vehicle')
            ->leftJoin('survey.passcodeUser', 'passcode_user')
            ->leftJoin('survey.response', 'response')
            ->leftJoin('response.vehicle', 'vehicle')
            ->where('survey.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }


    /**
     * @param bool $isNorthernIreland
     * @return Survey[]
     */
    public function findByTypeWithResponseAndVehicle($isNorthernIreland = false)
    {
        return $this->createQueryBuilder('survey')
            ->select('survey, passcode_user, response, vehicle')
            ->leftJoin('survey.passcodeUser', 'passcode_user')
            ->leftJoin('survey.response', 'response')
            ->leftJoin('response.vehicle', 'vehicle')
            ->where('survey.isNorthernIreland = :isNI')
            ->setParameter('isNI', $isNorthernIreland)
            ->getQuery()
            ->execute();
    }

    // /**
    //  * @return Survey[] Returns an array of Survey objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Survey
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function queryForExport()
    {
        $true = 1;
        $false = 0;
//        $const = fn($constant) => $constant;

        $expandConstBitMap = function($field, $map, $addCommaToLastItem = false) use ($true, $false) {
            $sql = "";
            foreach ($map as $constValue => $exportName) {
                $sql .= (($constValue === 'null')
                    ? "(CASE WHEN {$field} IS NULL THEN {$true} ELSE {$false} END) as {$exportName}"
                    : "(CASE WHEN {$field} = '{$constValue}' THEN {$true} ELSE {$false} END) as {$exportName}");
                if ($addCommaToLastItem || (array_key_last($map) !== $constValue)) {
                    $sql .= ", ";
                }
            }
            return $sql;
        };

        return $this->createQueryBuilder('survey')
            ->leftJoin('survey.response', 'response')
            ->leftJoin('response.vehicle', 'vehicle')
            ->leftJoin('response.days', 'days')
//            ->leftJoin('days.stops', 'stops')
//            ->leftJoin('days.summary', 'summary')
            ->select("
vehicle.registrationMark as RegMark,
vehicle.carryingCapacity as CarryingCapacity,
0 as CarryingCapacityNotEntered,
{$expandConstBitMap('vehicle.operationType', [
    Vehicle::OPERATION_TYPE_ON_OWN_ACCOUNT => 'OnOwnAccount',
    Vehicle::OPERATION_TYPE_FOR_HIRE_AND_REWARD => 'ForHireReward',
    'null' => 'MainlyOperatedNotEntered',
])},
{$false} as KMsLoadedYN,
{$true} as MilesLoadedYN,
{$false} as KMsEmptyYN,
{$true} as MilesEmptyYN,
{$expandConstBitMap('vehicle.bodyType', [
    Vehicle::BODY_TYPE_FLAT_DROP => 'FlatDropSided',
    Vehicle::BODY_TYPE_BOX => 'BoxNonSpecialised',
    Vehicle::BODY_TYPE_TEMPERATURE_CONTROLLED => 'TemperatureControlled',
    Vehicle::BODY_TYPE_CURTAIN_SIDED => 'CurtainSided',
    Vehicle::BODY_TYPE_LIQUID => 'LiquidTanker',
    Vehicle::BODY_TYPE_SOLID_BULK => 'SolidBulkTanker',
    Vehicle::BODY_TYPE_LIVESTOCK => 'LiveStockCarrier',
    Vehicle::BODY_TYPE_CAR => 'CarTansporter',
    Vehicle::BODY_TYPE_TIPPER => 'Tipper',
    Vehicle::BODY_TYPE_OTHER => 'Other',
    'null' => 'TrailerTypeNotEntered',
])},
{$false} as KMsLoadedYN5Plus,
{$true} as MilesLoadedYN5Plus,
{$false} as KMsEmptyYN5Plus,
{$true} as MilesEmptyYN5Plus,
'' as LastAddressLineDesc,
survey.surveyPeriodStart as SurveyStartDate,
vehicle.fuelQuantity.value as LitresOfFuelPurchased,
{$expandConstBitMap('vehicle.fuelQuantity.value', [
    Volume::UNIT_GALLONS => 'Gallons',
    Volume::UNIT_LITRES => 'Litres',
])},
response.businessNature as BusinessType,
survey.notifiedDate as DateSurveySent,
survey.submissionDate as DateSurveyReturned,
survey.invitationAddress.line1 as RegisteredKeeper,
survey.invitationAddress.line2 as SurveyAddressLine1,
survey.invitationAddress.line3 as SurveyAddressLine2,
survey.invitationAddress.line4 as SurveyAddressLine3,
survey.invitationAddress.line5 as SurveyAddressLine4,
survey.invitationAddress.line6 as SurveyAddressLine5,
survey.invitationAddress.postcode as SurveyPostcode,
response.contactTelephone as ContactTelNo




            ")
            ->getQuery()
//            ->getDQL()
            ->execute()
            ;

// ** extra fields needed from DVLA import **
// FuelTypePropulsionCode
// WheelPlanCode
// TaxationClass
// BodyTypeCodeID
// YearOfRegistration
// ArticRigid
// UnladenWeight
// GrossTrainWeight
// BodyTypeDescription


// ** Excluded fields **
// DateInput - unsure
// DateCompleted - unsure
// QuestionaireType - unsure
// SurveyType - unsure
// TripDataLoaded - unsure
// RecordLastUpdatedBy - unsure
// Comments - contains on hire, etc.
// CaryingCapacityAdjusted - will all be null, not required
// SurveyID - unsure
// VehicleOwnerID - unsure
// DateReminder1 - not sending
// DateReminder2 - not sending
// SurveyDiscardedReasonID - unsure
// SurveyEmail - unsure (invitation or contact)
// FuelTypePropulsionCode - unsure

    }
}
