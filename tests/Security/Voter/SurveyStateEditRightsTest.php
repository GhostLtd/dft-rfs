<?php

namespace App\Tests\Security\Voter;

use App\Entity\Domestic\Survey;
use App\Entity\SurveyStateInterface;
use App\Security\Voter\SurveyVoter;
use App\Tests\DataFixtures\Domestic\SurveyFixtures;

class SurveyStateEditRightsTest extends AbstractVoterTest
{
    public function passcodeUserData(): array
    {
        return [
            [SurveyStateInterface::STATE_IN_PROGRESS, true],
            [SurveyStateInterface::STATE_NEW, true],
            [SurveyStateInterface::STATE_INVITATION_PENDING, true],
            [SurveyStateInterface::STATE_INVITATION_FAILED, true],
            [SurveyStateInterface::STATE_INVITATION_SENT, true],
            [SurveyStateInterface::STATE_CLOSED, false],
            [SurveyStateInterface::STATE_REJECTED, false],
            [Survey::STATE_REISSUED, false],
            [SurveyStateInterface::STATE_APPROVED, false],
            [SurveyStateInterface::STATE_EXPORTED, false],
            [SurveyStateInterface::STATE_EXPORTING, false],
        ];
    }

    /**
     * @dataProvider passcodeUserData
     */
    public function testPasscodeUserAccess($state, $canEdit)
    {
        $this->loadFixtures([SurveyFixtures::class]);

        /** @var Survey $survey */
        $survey = $this->fixtureReferenceRepository->getReference('survey:simple', Survey::class);

        $this->createAndStorePasscodeUserToken($survey->getPasscodeUser());

        $survey->setState($state);
        self::assertSame($canEdit, $this->authChecker->isGranted(SurveyVoter::EDIT, $survey));
    }

    public function adminUserData(): array
    {
        return [
            [SurveyStateInterface::STATE_IN_PROGRESS, true],
            [SurveyStateInterface::STATE_NEW, true],
            [SurveyStateInterface::STATE_INVITATION_PENDING, true],
            [SurveyStateInterface::STATE_INVITATION_FAILED, true],
            [SurveyStateInterface::STATE_INVITATION_SENT, true],
            [SurveyStateInterface::STATE_CLOSED, true],
            [SurveyStateInterface::STATE_REJECTED, false],
            [Survey::STATE_REISSUED, false],
            [SurveyStateInterface::STATE_APPROVED, false],
            [SurveyStateInterface::STATE_EXPORTED, false],
            [SurveyStateInterface::STATE_EXPORTING, false],
        ];
    }

    /**
     * @dataProvider adminUserData
     */
    public function testAdminUserAccess(string $state, bool $expectedToBeAbleToEdit): void
    {
        $this->loadFixtures([SurveyFixtures::class]);
        /** @var Survey $survey */
        $survey = $this->fixtureReferenceRepository->getReference('survey:simple', Survey::class);
        $survey->setState($state);

        $this->createAndStoreAdminUserToken();

        self::assertSame($expectedToBeAbleToEdit, $this->authChecker->isGranted(SurveyVoter::EDIT, $survey));
    }
}
