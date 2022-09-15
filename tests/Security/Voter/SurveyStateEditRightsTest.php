<?php


namespace App\Tests\Security\Voter;


use App\Entity\Domestic\Survey;
use App\Security\Voter\SurveyVoter;
use App\Tests\DataFixtures\Domestic\SurveyFixtures;

class SurveyStateEditRightsTest extends AbstractVoterTest
{
    public function passcodeUserData(): array
    {
        return [
            [Survey::STATE_IN_PROGRESS, true],
            [Survey::STATE_NEW, true],
            [Survey::STATE_INVITATION_PENDING, true],
            [Survey::STATE_INVITATION_FAILED, true],
            [Survey::STATE_INVITATION_SENT, true],
            [Survey::STATE_CLOSED, false],
            [Survey::STATE_REJECTED, false],
            [Survey::STATE_REISSUED, false],
            [Survey::STATE_APPROVED, false],
            [Survey::STATE_EXPORTED, false],
            [Survey::STATE_EXPORTING, false],
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
            [Survey::STATE_IN_PROGRESS, true],
            [Survey::STATE_NEW, true],
            [Survey::STATE_INVITATION_PENDING, true],
            [Survey::STATE_INVITATION_FAILED, true],
            [Survey::STATE_INVITATION_SENT, true],
            [Survey::STATE_CLOSED, true],
            [Survey::STATE_REJECTED, false],
            [Survey::STATE_REISSUED, false],
            [Survey::STATE_APPROVED, false],
            [Survey::STATE_EXPORTED, false],
            [Survey::STATE_EXPORTING, false],
        ];
    }

    /**
     * @dataProvider adminUserData
     */
    public function testAdminUserAccess($state, $canEdit)
    {
        $this->loadFixtures([SurveyFixtures::class]);
        /** @var Survey $survey */
        $survey = $this->fixtureReferenceRepository->getReference('survey:simple');
        $survey->setState(Survey::STATE_CLOSED);

        $this->createAndStoreAdminUserToken();

        self::assertTrue($this->authChecker->isGranted(SurveyVoter::EDIT, $survey));
    }

}