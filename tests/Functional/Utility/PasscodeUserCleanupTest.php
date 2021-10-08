<?php

namespace App\Tests\Functional\Utility;

use App\Entity\AuditLog\AuditLog;
use App\Entity\Domestic\Survey;
use App\Entity\SurveyInterface;
use App\Tests\DataFixtures\SurveyFixtures;
use App\Tests\Functional\AbstractFunctionalTest;
use App\Utility\Cleanup\PasscodeUserCleanupUtility;
use Doctrine\ORM\EntityManagerInterface;

class PasscodeUserCleanupTest extends AbstractFunctionalTest
{
    protected PasscodeUserCleanupUtility $cleanupUtility;
    protected EntityManagerInterface $entityManager;

    public function setUp()
    {
        parent::setUp();

        $container = self::$kernel->getContainer();
        $this->cleanupUtility = $container->get(PasscodeUserCleanupUtility::class);
        $this->entityManager = $container->get('doctrine.orm.entity_manager');
    }

    public function cleanupData(): array
    {
        return [
            ['-7 months', SurveyInterface::STATE_APPROVED, false],
            ['-7 months', SurveyInterface::STATE_CLOSED, false],
            ['-7 months', SurveyInterface::STATE_EXPORTING, false],
            ['-7 months', SurveyInterface::STATE_EXPORTED, false],
            ['-7 months', SurveyInterface::STATE_IN_PROGRESS, false],
            ['-7 months', SurveyInterface::STATE_INVITATION_SENT, false],
            ['-7 months', SurveyInterface::STATE_INVITATION_PENDING, false],
            ['-7 months', SurveyInterface::STATE_INVITATION_FAILED, false],
            ['-7 months', SurveyInterface::STATE_NEW, false],
            ['-7 months', Survey::STATE_REISSUED, false],
            ['-7 months', SurveyInterface::STATE_REJECTED, true],

            ['-4 months', SurveyInterface::STATE_APPROVED, false],
            ['-4 months', SurveyInterface::STATE_CLOSED, false],
            ['-4 months', SurveyInterface::STATE_EXPORTING, false],
            ['-4 months', SurveyInterface::STATE_EXPORTED, false],
            ['-4 months', SurveyInterface::STATE_IN_PROGRESS, false],
            ['-4 months', SurveyInterface::STATE_INVITATION_SENT, false],
            ['-4 months', SurveyInterface::STATE_INVITATION_PENDING, false],
            ['-4 months', SurveyInterface::STATE_INVITATION_FAILED, false],
            ['-4 months', SurveyInterface::STATE_NEW, false],
            ['-4 months', Survey::STATE_REISSUED, false],
            ['-4 months', SurveyInterface::STATE_REJECTED, false],
        ];
    }

    /**
     * @dataProvider cleanupData
     */
    public function testCleanup(string $auditOffset, string $state, bool $expectedPasscodeUserDeleted)
    {
        $this->loadFixtures([SurveyFixtures::class]);
        $survey = $this->getSurveyAndSetState($auditOffset, $state);

        $this->cleanupUtility->cleanupPasscodeUsersBefore(new \DateTime('6 months ago'));

        $this->assertEquals(
            $expectedPasscodeUserDeleted,
            $survey->getPasscodeUser() === null,
            "Passcode user".($expectedPasscodeUserDeleted ? "" : " not")." deleted");
    }

    protected function getSurveyAndSetState(string $auditOffset, string $state): Survey
    {
        /** @var Survey $survey */
        $survey = $this->entityManager
            ->getRepository(Survey::class)
            ->findOneBy([]);

        $this->entityManager->flush();

        $survey->setState($state);

        // So that the "new" -> "invitation pending" state change doesn't have the exact same timestamp as the
        // "invitation pending" -> $state one
        sleep(1);

        $this->entityManager->flush();

        /**
         * @var AuditLog[] $auditLogs
         */
        $auditLogs = $this->entityManager
            ->getRepository(AuditLog::class)
            ->findBy(['entityId' => $survey->getId()]);

        // We need the auditLog state change timestamps to be in the past, so we artificially modify them here so
        // that we can test various scenarios
        foreach($auditLogs as $auditLog) {
            $modifiedTimestamp = (clone $auditLog->getTimestamp())->modify($auditOffset);
            $auditLog->setTimestamp($modifiedTimestamp);
        }

        $this->entityManager->flush();

        return $survey;
    }
}