<?php

namespace App\Tests\Security\Voter;

use App\Entity\RoRo\Operator;
use App\Entity\RoRo\Survey;
use App\Entity\RoRoUser;
use App\Security\Voter\RoRo\OperatorSwitchVoter;
use App\Security\Voter\RoRo\SurveyVoter;
use App\Tests\DataFixtures\TestSpecific\RoRoVoterFixtures;
use Doctrine\ORM\NonUniqueResultException;

class RoRoVoterTest extends AbstractVoterTest
{
    public function dataCanSwitch(): array
    {
        return [
            ['user:test-dover', true, false],
            ['user:test-portsmouth', true, false],
            ['user:toast-portsmouth', false, false],

            // There are two groups in the fixtures that overlap in terms of prefix

            // It shouldn't be possible to insert such a group via the admin, but if one does exist, let's
            // make sure it doesn't permit access (and indeed, throws an exception instead)
            ['user:illegalgroup-broken', null, true],
        ];
    }

    /**
     * @dataProvider dataCanSwitch
     */
    public function testCanSwitch(string $userRef, ?bool $expectedToBeAbleToSwitch, bool $expectedToThrowException)
    {
        $this->loadFixtures([RoRoVoterFixtures::class]);

        /** @var RoRoUser $user */
        $user = $this->fixtureReferenceRepository->getReference($userRef, RoRoUser::class);

        $this->createAndStorePasscodeUserToken($user);

        if ($expectedToThrowException) {
            $this->expectException(NonUniqueResultException::class);
        }

        $canSwitch = $this->authChecker->isGranted(OperatorSwitchVoter::CAN_SWITCH_OPERATOR);
        $this->assertEquals($expectedToBeAbleToSwitch, $canSwitch);
    }

    public function dataCanViewDashboard(): array
    {
        return [
            ['user:test-dover', 'operator:test-dover', true, false],
            ['user:test-dover', 'operator:test-portsmouth', true, false],
            ['user:test-dover', 'operator:toast-portsmouth', false, false],
            ['user:test-portsmouth', 'operator:test-dover', true, false],
            ['user:test-portsmouth', 'operator:test-portsmouth', true, false],
            ['user:test-portsmouth', 'operator:toast-portsmouth', false, false],
            ['user:toast-portsmouth', 'operator:toast-portsmouth', true, false],

            // See above...
            ['user:illegalgroup-broken', 'operator:test-portsmouth', null, true],
        ];
    }

    /**
     * @dataProvider dataCanViewDashboard
     */
    public function testCanViewDashboard(string $userRef, string $operatorRef, ?bool $expectedToBeAbleToView, bool $expectedToThrowException)
    {
        $this->loadFixtures([RoRoVoterFixtures::class]);

        /** @var RoRoUser $user */
        $user = $this->fixtureReferenceRepository->getReference($userRef, RoRoUser::class);

        /** @var Operator $operator */
        $operator = $this->fixtureReferenceRepository->getReference($operatorRef, Operator::class);

        $this->createAndStorePasscodeUserToken($user);

        if ($expectedToThrowException) {
            $this->expectException(NonUniqueResultException::class);
        }

        $canView = $this->authChecker->isGranted(SurveyVoter::CAN_VIEW_RORO_DASHBOARD, $operator);
        $this->assertEquals($expectedToBeAbleToView, $canView);
    }


    public function dataCanViewSurvey(): array
    {
        return [
            ['user:test-dover', 'survey:test-dover', true, false],
            ['user:test-dover', 'survey:test-portsmouth', true, false],
            ['user:test-dover', 'survey:toast-portsmouth', false, false],
            ['user:test-portsmouth', 'survey:test-dover', true, false],
            ['user:test-portsmouth', 'survey:test-portsmouth', true, false],
            ['user:test-portsmouth', 'survey:toast-portsmouth', false, false],
            ['user:toast-portsmouth', 'survey:toast-portsmouth', true, false],

            // See above...
            ['user:illegalgroup-broken', 'survey:test-portsmouth', null, true],
        ];
    }

    /**
     * @dataProvider dataCanViewSurvey
     */
    public function testCanViewSurvey(string $userRef, string $surveyRef, ?bool $expectedToBeAbleToView, bool $expectedToThrowException)
    {
        $this->loadFixtures([RoRoVoterFixtures::class]);

        /** @var RoRoUser $user */
        $user = $this->fixtureReferenceRepository->getReference($userRef, RoRoUser::class);

        /** @var Survey $survey */
        $survey = $this->fixtureReferenceRepository->getReference($surveyRef, Survey::class);

        $this->createAndStorePasscodeUserToken($user);

        if ($expectedToThrowException) {
            $this->expectException(NonUniqueResultException::class);
        }

        $canView = $this->authChecker->isGranted(SurveyVoter::CAN_VIEW_RORO_SURVEY, $survey);
        $this->assertEquals($expectedToBeAbleToView, $canView);
    }

    public function dataCanEditSurvey(): array
    {
        return [
            ['user:test-dover', 'survey:test-dover', true, false],
            ['user:test-dover', 'survey:test-portsmouth', false, false],        // This survey is closed
            ['user:test-dover', 'survey:toast-portsmouth', false, false],
            ['user:test-portsmouth', 'survey:test-dover', true, false],
            ['user:test-portsmouth', 'survey:test-portsmouth', false, false],   // Ditto
            ['user:test-portsmouth', 'survey:toast-portsmouth', false, false],
            ['user:toast-portsmouth', 'survey:toast-portsmouth', true, false],

            // See above...
            ['user:illegalgroup-broken', 'survey:test-portsmouth', null, true],
        ];
    }

    /**
     * @dataProvider dataCanEditSurvey
     */
    public function testCanEditSurvey(string $userRef, string $surveyRef, ?bool $expectedToBeAbleToEdit, bool $expectedToThrowException)
    {
        $this->loadFixtures([RoRoVoterFixtures::class]);

        /** @var RoRoUser $user */
        $user = $this->fixtureReferenceRepository->getReference($userRef, RoRoUser::class);

        /** @var Survey $survey */
        $survey = $this->fixtureReferenceRepository->getReference($surveyRef, Survey::class);

        $this->createAndStorePasscodeUserToken($user);

        if ($expectedToThrowException) {
            $this->expectException(NonUniqueResultException::class);
        }

        $canView = $this->authChecker->isGranted(SurveyVoter::CAN_EDIT_RORO_SURVEY, $survey);
        $this->assertEquals($expectedToBeAbleToEdit, $canView);
    }
}
