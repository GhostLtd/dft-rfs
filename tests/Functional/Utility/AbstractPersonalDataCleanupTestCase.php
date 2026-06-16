<?php

namespace App\Tests\Functional\Utility;

use App\Entity\AuditLog\AuditLog;
use App\Entity\SurveyInterface;
use App\Tests\DataFixtures\UserFixtures;
use App\Tests\Functional\AbstractFunctionalTestCase;
use App\Utility\Cleanup\PersonalDataCleanupInterface;
use App\Utility\Cleanup\PersonalDataCleanupUtility;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;

abstract class AbstractPersonalDataCleanupTestCase extends AbstractFunctionalTestCase
{
    protected ?PersonalDataCleanupUtility $cleanupUtility;
    protected ?EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cleanupUtility = static::getContainer()->get(PersonalDataCleanupUtility::class);
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    abstract public static function dataCleanup(): array;

    #[DataProvider('dataCleanup')]
    public function testCleanup(string $currentDate, string $surveyPeriodEnd, bool $createAuditLogEntry, bool $expectedPersonalDataDeleted): void
    {
        $this->loadFixtures([UserFixtures::class]);

        $survey = $this->createSurveyFixture(new \DateTime($surveyPeriodEnd), $createAuditLogEntry);

        $this->cleanupUtility
            ->setCurrentDateForTesting(new \DateTime($currentDate))
            ->cleanupPersonalData();

        $this->assertEquals(
            $expectedPersonalDataDeleted,
            $survey->isPersonalDataCleared(),
            "Expected personal details to".($expectedPersonalDataDeleted ? "" : " not")." be deleted"
        );
    }

    abstract protected function createSurveyFixture(\DateTime $surveyPeriodEnd, bool $createAuditLogEntry): SurveyInterface&PersonalDataCleanupInterface;

    protected function createAuditLogEntry(SurveyInterface $survey): void
    {
        $auditLog = (new AuditLog())
            ->setCategory(PersonalDataCleanupUtility::CATEGORY)
            ->setUsername('-')
            ->setEntityId($survey->getId())
            ->setEntityClass(get_class($survey))
            ->setTimestamp(new \DateTime())
            ->setData([]);

        $this->entityManager->persist($auditLog);
    }
}
