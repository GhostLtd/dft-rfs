<?php

namespace App\Repository\Domestic;

use App\Doctrine\Hydrators\ColumnHydrator;
use App\Entity\Domestic\NotificationInterception;
use App\Entity\Domestic\Survey;
use App\Entity\SurveyStateInterface;
use App\Repository\DashboardStatsTrait;
use App\Repository\SurveyDeletionInterface;
use App\Utility\Quarter\CsrgtQuarterHelper;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use RuntimeException;

/**
 * @method Survey|null find($id, $lockMode = null, $lockVersion = null)
 * @method Survey|null findOneBy(array $criteria, array $orderBy = null)
 * @method Survey[]    findAll()
 * @method Survey[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SurveyRepository extends ServiceEntityRepository implements SurveyDeletionInterface
{
    use DashboardStatsTrait;

    public function __construct(
        ManagerRegistry              $registry,
        protected CsrgtQuarterHelper $quarterHelper,
    )
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
     * @return array<Survey>
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
        $dateRange = $this->quarterHelper->getDateRangeForYearAndQuarter($year, $quarter);

        return $this->createQueryBuilder('survey')
            ->select('survey.id')
            ->leftJoin('survey.response', 'response')
            ->where('survey.surveyPeriodStart >= :quarterStart')
            ->andWhere('survey.surveyPeriodStart < :quarterEnd')
            ->andWhere('survey.state IN (:states)')
            ->orderBy('survey.surveyPeriodStart', 'ASC')
            ->addOrderBy('survey.id', 'ASC')
            ->setParameters(new ArrayCollection([
                new Parameter('quarterStart', $dateRange[0]),
                new Parameter('quarterEnd', $dateRange[1]),
                new Parameter('states', [
                    Survey::STATE_NEW, Survey::STATE_INVITATION_PENDING, Survey::STATE_INVITATION_FAILED,
                    Survey::STATE_INVITATION_SENT, Survey::STATE_IN_PROGRESS, Survey::STATE_CLOSED,
                    Survey::STATE_APPROVED,
                ]),
            ]))
            ->getQuery()
            ->getResult(ColumnHydrator::class);
    }

    /**
     * @return array<Survey>
     */
    public function findForExport($surveyIDs): array
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
            ->setParameter('surveyIDs', $surveyIDs)
            ->getQuery()
            ->getResult();

        if (count($surveyIDs) !== count($counts)) {
            throw new RuntimeException("survey count mismatch");
        }
        if (count($surveyIDs) !== count($surveys)) {
            throw new RuntimeException("survey count mismatch");
        }

        foreach ($counts as $count) {
            $surveyId = $count['id'];
            if (!isset($surveys[$surveyId])) {
                throw new RuntimeException("Survey not found: {$surveyId}");
            }
            if ($surveys[$surveyId]->getResponse()) {
                $surveys[$surveyId]->getResponse()->_summaryCountForExport = $count['summaryCount'];
                $surveys[$surveyId]->getResponse()->_stopCountForExport = $count['stopCount'];
            }
        }

        return $surveys;
    }

    public function getOverdueCount(bool $isNortherIreland): int
    {
        return $this->createQueryBuilder('s')
            ->select('count(s) AS count')
            ->where('s.isNorthernIreland = :isNorthernIreland')
            ->andWhere('s.surveyPeriodEnd < :now')
            ->andWhere('s.state IN (:activeStates)')
            ->setParameters(new ArrayCollection([
                new Parameter('now', new DateTime()),
                new Parameter('activeStates', SurveyStateInterface::ACTIVE_STATES),
                new Parameter('isNorthernIreland', $isNortherIreland),
            ]))
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getInProgressCount(bool $isNortherIreland): int
    {
        return $this->createQueryBuilder('s')
            ->select('count(s) AS count')
            ->where('s.isNorthernIreland = :isNorthernIreland')
            ->andWhere('s.state IN (:activeStates)')
            ->setParameters(new ArrayCollection([
                new Parameter('activeStates', SurveyStateInterface::ACTIVE_STATES),
                new Parameter('isNorthernIreland', $isNortherIreland),
            ]))
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array<Survey>
     */
    public function findSurveysMatchingLcniAddressAndEmails(NotificationInterception $interception): array
    {
        return $this->lcniFindQuery(
            $interception,
            fn(QueryBuilder $qb) => $qb
                ->andWhere('s.invitationEmails = :emails')
                ->setParameter('emails', $interception->getEmails())
        );
    }

    /**
     * @return array<Survey>
     */
    public function findSurveysMatchingLcniAddressWithNoEmails(NotificationInterception $interception): array
    {
        return $this->lcniFindQuery(
            $interception,
            fn(QueryBuilder $qb) => $qb->andWhere('s.invitationEmails IS NULL')
        );
    }

    /**
     * @return array<Survey>
     */
    protected function lcniFindQuery(NotificationInterception $interception, \Closure $callback): array
    {
        $qb = $this->createQueryBuilder('s')
            ->select('s')
            ->where('s.invitationAddress.line1 = :addressLine')
            ->andWhere('s.state IN (:states)')
            ->setParameter('addressLine', $interception->getAddressLine())
            ->setParameter('states', [
                SurveyStateInterface::STATE_NEW,
                SurveyStateInterface::STATE_INVITATION_FAILED,
                SurveyStateInterface::STATE_INVITATION_PENDING,
                SurveyStateInterface::STATE_INVITATION_SENT,
                SurveyStateInterface::STATE_IN_PROGRESS,
            ]);

        $callback($qb);

        return $qb
            ->getQuery()
            ->execute();
    }

    /**
     * @return array{minDate: string, maxDate: string}
     */
    public function getSubmissionDateRange(): array
    {
        return $this->createQueryBuilder('s')
            ->select('MIN(s.submissionDate) AS minDate, MAX(s.submissionDate) AS maxDate')
            ->where('s.submissionDate IS NOT NULL')
            ->getQuery()
            ->getSingleResult();
    }

    public function getSurveysForDeletion(\DateTime $before): array
    {
        return $this->createQueryBuilder('survey')
            ->select('survey, response, days, stops, summary, notes')
            ->where('survey.surveyPeriodEnd < :before')
            ->leftJoin('survey.response', 'response')
            ->leftJoin('response.days', 'days')
            ->leftJoin('days.stops', 'stops')
            ->leftJoin('days.summary', 'summary')
            ->leftJoin('survey.notes', 'notes')
            ->setParameter('before', $before->format('Y-m-d'))
            ->getQuery()
            ->execute();
    }
}
