<?php

namespace App\Tests\Functional\Utility;

use App\Entity\Domestic\Survey;
use App\Entity\SurveyInterface;
use App\Tests\DataFixtures\Domestic\SurveyFixtures;
use App\Utility\Cleanup\PersonalDataCleanupUtility;

class PersonalDataCleanupTest extends AbstractCleanupTest
{
    protected ?PersonalDataCleanupUtility $cleanupUtility;

    public function setUp()
    {
        parent::setUp();

        $container = self::$kernel->getContainer();
        $this->cleanupUtility = $container->get(PersonalDataCleanupUtility::class);
    }

    public function dataCleanup(): array
    {
        return [
            ['-7 months', SurveyInterface::STATE_APPROVED, false],
            ['-7 months', SurveyInterface::STATE_CLOSED, false],
            ['-7 months', SurveyInterface::STATE_IN_PROGRESS, false],
            ['-7 months', SurveyInterface::STATE_INVITATION_SENT, false],
            ['-7 months', SurveyInterface::STATE_INVITATION_PENDING, false],
            ['-7 months', SurveyInterface::STATE_INVITATION_FAILED, false],
            ['-7 months', SurveyInterface::STATE_NEW, false],
            ['-7 months', SurveyInterface::STATE_EXPORTING, false],
            ['-7 months', SurveyInterface::STATE_REJECTED, true],
            ['-7 months', Survey::STATE_REISSUED, true],
            ['-7 months', SurveyInterface::STATE_EXPORTED, true],

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
     * @dataProvider dataCleanup
     */
    public function testCleanup(string $auditOffset, string $state, bool $expectedPersonalDataDeleted)
    {
        $this->loadFixtures([SurveyFixtures::class]);
        $survey = $this->getSurveyAndSetState($auditOffset, $state);

        $this->cleanupUtility->cleanupPersonalDataBefore(new \DateTime('6 months ago'));

        $this->assertEquals(
            $expectedPersonalDataDeleted,
            $survey->isPersonalDataCleared(),
            "Personal details".($expectedPersonalDataDeleted ? "" : " not")." deleted");
    }
}