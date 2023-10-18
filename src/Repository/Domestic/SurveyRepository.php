<?php

namespace App\Repository\Domestic;

use App\Doctrine\Hydrators\ColumnHydrator;
use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyResponse;
use App\Entity\SurveyStateInterface;
use App\Repository\DashboardStatsTrait;
use App\Repository\AbstractSurveyRepository;
use App\Utility\Domestic\WeekNumberHelper;
use App\Utility\StateReportHelper;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use RuntimeException;

/**
 * @method Survey|null find($id, $lockMode = null, $lockVersion = null)
 * @method Survey|null findOneBy(array $criteria, array $orderBy = null)
 * @method Survey[]    findAll()
 * @method Survey[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SurveyRepository extends AbstractSurveyRepository
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
    public function findByTypeWithResponseAndVehicle(bool $isNorthernIreland = false): array
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

    public function getSurveyIDsForExport($year, $quarter): array
    {
        $dateRange = WeekNumberHelper::getDateRangeForYearAndQuarter($year, $quarter);

        return $this->createQueryBuilder('survey')
            ->select('survey.id')
            ->leftJoin('survey.response', 'response')
            ->where('survey.surveyPeriodStart >= :quarterStart')
            ->andWhere('survey.surveyPeriodStart < :quarterEnd')
            ->andWhere('survey.state IN (:states)')
            ->orderBy('survey.surveyPeriodStart', 'ASC')
            ->addOrderBy('survey.id', 'ASC')
            ->setParameters([
                'quarterStart' => $dateRange[0],
                'quarterEnd' => $dateRange[1],
                'states' => [
                    Survey::STATE_NEW, Survey::STATE_INVITATION_PENDING, Survey::STATE_INVITATION_FAILED,
                    Survey::STATE_INVITATION_SENT, Survey::STATE_IN_PROGRESS, Survey::STATE_CLOSED,
                    Survey::STATE_APPROVED,
                ],
            ])
            ->getQuery()
            ->getResult(ColumnHydrator::class);
    }

    /**
     * @return Survey[] | ArrayCollection
     */
    public function findForExport($surveyIDs)
    {
        $counts = $this->createQueryBuilder('survey')
            ->select('survey.id, COUNT(summary) as summaryCount, COUNT(stops) as stopCount')
            ->leftJoin('survey.response', 'response')
            ->leftJoin('response.days', 'days')
            ->leftJoin('days.stops', 'stops')
            ->leftJoin('days.summary', 'summary')
            ->groupBy('survey.id')
            ->orderBy('survey.surveyPeriodStart', 'ASC')
            ->addOrderBy('survey.id', 'ASC')
            ->where('survey.id IN (:surveyIDs)')
            ->setParameter('surveyIDs', $surveyIDs)
            ->getQuery()
            ->execute();

        $surveys = $this->createQueryBuilder('survey', 'survey.id')
            ->select('survey, passcode_user, response, vehicle, reissued')
            ->leftJoin('survey.passcodeUser', 'passcode_user')
            ->leftJoin('survey.response', 'response')
            ->leftJoin('response.vehicle', 'vehicle')
            ->leftJoin('survey.reissuedSurvey', 'reissued')
            ->where('survey.id IN (:surveyIDs)')
            ->orderBy('survey.surveyPeriodStart', 'ASC')
            ->addOrderBy('survey.id', 'ASC')
            ->setParameters([
                'surveyIDs' => $surveyIDs,
            ])
            ->getQuery()
            ->getResult();

        if (count($surveyIDs) !== count($counts)) {
            throw new RuntimeException("survey count mismatch");
        }
        if (count($surveyIDs) !== count($surveys)) {
            throw new RuntimeException("survey count mismatch");
        }

        /** @var Survey $survey */
        foreach ($counts as $count) {
            if (!isset($surveys[$count['id']])) {
                throw new RuntimeException("Survey not found: {$survey->getId()}");
            }
            if ($surveys[$count['id']]->getResponse()) {
                $surveys[$count['id']]->getResponse()->_summaryCountForExport = $count['summaryCount'];
                $surveys[$count['id']]->getResponse()->_stopCountForExport = $count['stopCount'];
            }
        }

//        $this->_em->clear();

        return $surveys;
    }

    public function getOverdueCount(bool $isNortherIreland): int
    {
        return $this->createQueryBuilder('s')
            ->select('count(s) AS count')
            ->where('s.isNorthernIreland = :isNorthernIreland')
            ->andWhere('s.surveyPeriodEnd < :now')
            ->andWhere('s.state IN (:activeStates)')
            ->setParameters([
                'now' => new DateTime(),
                'activeStates' => SurveyStateInterface::ACTIVE_STATES,
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
                'activeStates' => SurveyStateInterface::ACTIVE_STATES,
                'isNorthernIreland' => $isNortherIreland,
            ])
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Survey[]|array
     */
    public function getSurveysRequiringPasscodeUserCleanup(DateTime $before): array
    {
        return $this->doGetSurveysRequiringPasscodeUserCleanup($before, 'domestic_survey');
    }

    /**
     * @return Survey[]|array
     */
    public function getSurveysRequiringPersonalDataCleanup(DateTime $before): array {
        $states = [SurveyStateInterface::STATE_REJECTED, SurveyStateInterface::STATE_EXPORTED, Survey::STATE_REISSUED];
        return $this->doGetSurveysRequiringPersonalDataCleanup($before, $states, 'domestic_survey');
    }
}
