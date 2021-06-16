<?php

namespace App\Repository\Domestic;

use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyResponse;
use App\Entity\SurveyInterface;
use App\Entity\Vehicle;
use App\Entity\Volume;
use App\Repository\DashboardStatsTrait;
use App\Utility\Domestic\WeekNumberHelper;
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
    use DashboardStatsTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Survey::class);
    }

    public function findOneByIdWithResponseAndVehicle($id): ?Survey
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
    public function findByTypeWithResponseAndVehicle($isNorthernIreland = false): array
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

    /**
     * @return Survey[]
     */
    public function findForExport($year, $quarter): array
    {
        $dateRange = WeekNumberHelper::getDateRangeForYearAndQuarter($year, $quarter);

        return $this->createQueryBuilder('survey')
            ->select('survey, passcode_user, response, vehicle, days, stops, summary')
            ->leftJoin('survey.passcodeUser', 'passcode_user')
            ->leftJoin('survey.response', 'response')
            ->leftJoin('response.vehicle', 'vehicle')
            ->leftJoin('response.days', 'days')
            ->leftJoin('days.stops', 'stops')
            ->leftJoin('days.summary', 'summary')
            ->where('survey.surveyPeriodStart >= :quarterStart')
            ->andWhere('survey.surveyPeriodStart < :quarterEnd')
            ->andWhere('survey.state = :state')
            ->andWhere('response.isInPossessionOfVehicle IN (:inPossessionCodes)')
            ->orderBy('survey.surveyPeriodStart', 'ASC')
            ->addOrderBy('survey.id', 'ASC')
            ->setParameters([
                'quarterStart' => $dateRange[0],
                'quarterEnd' => $dateRange[1],
                'state' => Survey::STATE_APPROVED,
                'inPossessionCodes' => [SurveyResponse::IN_POSSESSION_YES, SurveyResponse::IN_POSSESSION_SCRAPPED_OR_STOLEN]
            ])
            ->getQuery()
            ->execute();
    }

    public function getOverdueCount(bool $isNortherIreland): int
    {
        return $this->createQueryBuilder('s')
            ->select('count(s) AS count')
            ->where('s.isNorthernIreland = :isNorthernIreland')
            ->andWhere('s.surveyPeriodEnd < :now')
            ->andWhere('s.state IN (:activeStates)')
            ->setParameters([
                'now' => new \DateTime(),
                'activeStates' => SurveyInterface::ACTIVE_STATES,
                'isNorthernIreland' => $isNortherIreland,
            ])
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getInProgressCount(bool $isNortherIreland): int
    {
        return $this->createQueryBuilder('s')
            ->select('count(s) AS count')
            ->where('s.isNorthernIreland = :isNorthernIreland')
            ->andWhere('s.state IN (:activeStates)')
            ->setParameters([
                'activeStates' => SurveyInterface::ACTIVE_STATES,
                'isNorthernIreland' => $isNortherIreland,
            ])
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function queryForExport()
    {
        $true = 1;
        $false = 0;
//        $c = fn($constant) => $constant;
//        $chr = 'chr';

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
survey.registrationMark as RegMark,
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

// crazy test chars
//'wobble,test
//	newwline{$chr(8)}{$chr(17)}' as TestField


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
// DateInput - team logged in to system (form returned)
// DateCompleted - team did data input (MS access)
        // also need a date approved
// QuestionaireType - 1 post? 0 email? (not needed)
// SurveyType - 1 = GB, 2 = NI, 3 = IRHS
// TripDataLoaded - don't know, not needed
// RecordLastUpdatedBy - approved by
// Comments - contains on hire, etc. - not use in export table
// CaryingCapacityAdjusted - will all be null, not required - lucy doesn't know
// SurveyID - current == 941 => 2020, week x (each week has a survey ID, like irhs)
// VehicleOwnerID - not needed
// DateReminder1 - not currently sending, handy to have
// DateReminder2 - not currently sending, handy to have
// SurveyDiscardedReasonID - lookup table
// SurveyEmail - invitation email - not needed for our export


    }
}
