<?php

namespace App\Tests\Functional\Utility;

use App\Entity\Domestic\Survey;
use App\Entity\SurveyInterface;
use App\Tests\DataFixtures\Domestic\SurveyFixtures;
use App\Utility\Cleanup\PasscodeUserCleanupUtility;

class PasscodeUserCleanupTest extends AbstractCleanupTest
{
    protected ?PasscodeUserCleanupUtility $cleanupUtility = null;

    public function setUp()
    {
        parent::setUp();

        $container = self::$kernel->getContainer();
        $this->cleanupUtility = $container->get(PasscodeUserCleanupUtility::class);
    }

    public function dataCleanup(): array
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
     * @dataProvider dataCleanup
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
}