<?php

namespace App\Tests\Security\Voter;

use App\Entity\Domestic\Survey;
use App\Entity\SurveyInterface;
use App\Security\Voter\SurveyVoter;
use App\Tests\DataFixtures\Domestic\SurveyFixtures;

class SurveyStateEditRightsTest extends AbstractVoterTest
{
    public function passcodeUserData(): array
    {
        return [
            [SurveyInterface::STATE_IN_PROGRESS, true],
            [SurveyInterface::STATE_NEW, true],
            [SurveyInterface::STATE_INVITATION_PENDING, true],
            [SurveyInterface::STATE_INVITATION_FAILED, true],
            [SurveyInterface::STATE_INVITATION_SENT, true],
            [SurveyInterface::STATE_CLOSED, false],
            [SurveyInterface::STATE_REJECTED, false],
            [Survey::STATE_REISSUED, false],
            [SurveyInterface::STATE_APPROVED, false],
            [SurveyInterface::STATE_EXPORTED, false],
            [SurveyInterface::STATE_EXPORTING, false],
        ];
    }

    /**
     * @dataProvider passcodeUserData
     */
    public function testPasscodeUserAccess($state, $canEdit)
    {
        $this->loadFixtures([SurveyFixtures::class]);
        /** @var Survey $survey */
        $survey = $this->fixtureReferenceRepository->getReference('survey:simple');

        $this->createAndStorePasscodeUserToken($survey->getPasscodeUser());

        $survey->setState($state);
        self::assertSame($canEdit, $this->authChecker->isGranted(SurveyVoter::EDIT, $survey));
    }

    public function adminUserData(): array
    {
        return [
            [SurveyInterface::STATE_IN_PROGRESS, true],
            [SurveyInterface::STATE_NEW, true],
            [SurveyInterface::STATE_INVITATION_PENDING, true],
            [SurveyInterface::STATE_INVITATION_FAILED, true],
            [SurveyInterface::STATE_INVITATION_SENT, true],
            [SurveyInterface::STATE_CLOSED, true],
            [SurveyInterface::STATE_REJECTED, false],
            [Survey::STATE_REISSUED, false],
            [SurveyInterface::STATE_APPROVED, false],
            [SurveyInterface::STATE_EXPORTED, false],
            [SurveyInterface::STATE_EXPORTING, false],
        ];
    }

    /**
     * @dataProvider adminUserData
     */
    public function testAdminUserAccess(string $state, bool $expectedToBeAbleToEdit): void
    {
        $this->loadFixtures([SurveyFixtures::class]);
        /** @var Survey $survey */
        $survey = $this->fixtureReferenceRepository->getReference('survey:simple');
        $survey->setState($state);

        $this->createAndStoreAdminUserToken();

        self::assertSame($expectedToBeAbleToEdit, $this->authChecker->isGranted(SurveyVoter::EDIT, $survey));
    }
}