<?php

namespace App\Tests\Repository\AuditLog;

use App\Entity\AuditLog\AuditLog;
use App\Entity\Domestic\Survey;
use App\Entity\SurveyStateInterface;
use App\Repository\AuditLog\AuditLogRepository;
use App\Tests\DataFixtures\UserFixtures;
use App\Utility\AuditEntityLogger\SurveyStateLogger;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The audit log's state queries read the JSON "data" column through GHOST_JSON_GET.
 * tests/MySQL/AuditLogStateQueriesTest runs these on MySQL.
 */
class AuditLogStateQueriesTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private EntityManagerInterface $auditLogEntityManager;
    private AuditLogRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        static::bootKernel();
        $container = static::getContainer();
        $container->get(DatabaseToolCollection::class)->get()->loadFixtures([UserFixtures::class]);
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->auditLogEntityManager = $container->get('doctrine.orm.audit_log_entity_manager');
        $this->repository = $container->get(AuditLogRepository::class);
    }

    public function testSurveyHasPreviouslyBeenInClosedState(): void
    {
        $reopened = $this->createSurvey('RE01 ABC');
        $neverClosed = $this->createSurvey('NC01 ABC');
        $this->logTransition($reopened, 'respondent', 'in-progress', 'closed', '2021-05-03 10:00');
        $this->logTransition($reopened, 'admin', 'closed', 'in-progress', '2021-05-04 10:00');
        $this->logTransition($neverClosed, 'admin', 'new', 'in-progress', '2021-05-03 10:00');

        $this->assertTrue($this->repository->surveyHasPreviouslyBeenInClosedState($reopened));
        $this->assertFalse($this->repository->surveyHasPreviouslyBeenInClosedState($neverClosed));
    }

    public function testGetApprovedByReturnsTheLatestApprover(): void
    {
        $survey = $this->createSurvey('AP01 ABC');
        $this->logTransition($survey, 'alice', 'closed', 'approved', '2021-05-03 10:00');
        $this->logTransition($survey, 'bob', 'approved', 'closed', '2021-05-04 10:00');
        $this->logTransition($survey, 'carol', 'closed', 'approved', '2021-05-05 10:00');

        $this->assertSame('carol', $this->repository->getApprovedBy($survey)['username']);
    }

    public function testDomesticApprovalReportCountsApprovalsByUser(): void
    {
        $this->logTransition($this->createSurvey('GB01 ABC'), 'alice', 'closed', 'approved', '2021-05-03 10:00');
        $this->logTransition($this->createSurvey('NI01 ABC', northernIreland: true), 'alice', 'closed', 'approved', '2021-05-04 10:00');
        $this->logTransition($this->createSurvey('GB02 ABC'), 'bob', 'closed', 'approved', '2021-05-05 10:00');
        $this->logTransition($this->createSurvey('GB03 ABC'), 'carol', 'new', 'in-progress', '2021-05-05 10:00');

        [$min, $max] = [new \DateTime('2021-05-01'), new \DateTime('2021-06-01')];

        $all = $this->repository->getDomesticApprovalReportStats($min, $max);
        $this->assertSame(['alice', 'bob'], $all['usernames']);
        $this->assertSame(['alice' => 2, 'bob' => 1], $all['totals']);

        $greatBritain = $this->repository->getDomesticApprovalReportStats($min, $max, false);
        $this->assertSame(['alice' => 1, 'bob' => 1], $greatBritain['totals']);
    }

    private function createSurvey(string $registrationMark, bool $northernIreland = false): Survey
    {
        $survey = (new Survey())
            ->setSurveyPeriodStart(new \DateTime('2021-05-03'))
            ->setSurveyPeriodEnd(new \DateTime('2021-05-09'))
            ->setIsNorthernIreland($northernIreland)
            ->setRegistrationMark($registrationMark)
            ->setState(SurveyStateInterface::STATE_APPROVED);
        $this->entityManager->persist($survey);
        $this->entityManager->flush();

        return $survey;
    }

    private function logTransition(Survey $survey, string $username, string $from, string $to, string $timestamp): void
    {
        $this->auditLogEntityManager->persist((new AuditLog())
            ->setEntityId($survey->getId())
            ->setEntityClass(Survey::class)
            ->setCategory(SurveyStateLogger::CATEGORY)
            ->setUsername($username)
            ->setTimestamp(new \DateTime($timestamp))
            ->setData(['from' => $from, 'to' => $to]));
        $this->auditLogEntityManager->flush();
    }
}
