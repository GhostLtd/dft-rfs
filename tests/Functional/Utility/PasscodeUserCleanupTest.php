<?php

namespace App\Tests\Functional\Utility;

use App\Entity\Domestic\Survey;
use App\Entity\SurveyInterface;
use App\Entity\SurveyStateInterface;
use App\Tests\DataFixtures\Domestic\SurveyFixtures;
use App\Utility\Cleanup\PasscodeUserCleanupUtility;

class PasscodeUserCleanupTest extends AbstractCleanupTest
{
    protected ?PasscodeUserCleanupUtility $cleanupUtility = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cleanupUtility = static::getContainer()->get(PasscodeUserCleanupUtility::class);
    }

    public function dataCleanup(): array
    {
        return [
            ['-7 months', SurveyStateInterface::STATE_APPROVED, false],
            ['-7 months', SurveyStateInterface::STATE_CLOSED, false],
            ['-7 months', SurveyStateInterface::STATE_EXPORTING, false],
            ['-7 months', SurveyStateInterface::STATE_EXPORTED, false],
            ['-7 months', SurveyStateInterface::STATE_IN_PROGRESS, false],
            ['-7 months', SurveyStateInterface::STATE_INVITATION_SENT, false],
            ['-7 months', SurveyStateInterface::STATE_INVITATION_PENDING, false],
            ['-7 months', SurveyStateInterface::STATE_INVITATION_FAILED, false],
            ['-7 months', SurveyStateInterface::STATE_NEW, false],
            ['-7 months', Survey::STATE_REISSUED, false],
            ['-7 months', SurveyStateInterface::STATE_REJECTED, true],

            ['-4 months', SurveyStateInterface::STATE_APPROVED, false],
            ['-4 months', SurveyStateInterface::STATE_CLOSED, false],
            ['-4 months', SurveyStateInterface::STATE_EXPORTING, false],
            ['-4 months', SurveyStateInterface::STATE_EXPORTED, false],
            ['-4 months', SurveyStateInterface::STATE_IN_PROGRESS, false],
            ['-4 months', SurveyStateInterface::STATE_INVITATION_SENT, false],
            ['-4 months', SurveyStateInterface::STATE_INVITATION_PENDING, false],
            ['-4 months', SurveyStateInterface::STATE_INVITATION_FAILED, false],
            ['-4 months', SurveyStateInterface::STATE_NEW, false],
            ['-4 months', Survey::STATE_REISSUED, false],
            ['-4 months', SurveyStateInterface::STATE_REJECTED, false],
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