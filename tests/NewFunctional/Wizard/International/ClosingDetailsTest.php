<?php

namespace App\Tests\NewFunctional\Wizard\International;

use App\Entity\International\Survey;
use App\Entity\SurveyStateInterface;
use App\Repository\International\SurveyRepository;
use App\Tests\DataFixtures\International\ActionFixtures;
use App\Tests\DataFixtures\International\EarlySurveyFixtures;
use App\Tests\DataFixtures\International\LoadingActionFixtures;
use App\Tests\DataFixtures\International\ResponseCeasedTradingFixtures;
use App\Tests\DataFixtures\International\ResponseDomesticOnlyFixtures;
use App\Tests\DataFixtures\International\ResponseStillActiveFixtures;
use App\Tests\NewFunctional\Wizard\AbstractPasscodeWizardTest;
use App\Tests\NewFunctional\Wizard\Action\Context;
use App\Tests\NewFunctional\Wizard\Form\FormTestCase;

class ClosingDetailsTest extends AbstractPasscodeWizardTest
{
    public function testFinalQuestionsWithEmptySurvey(): void
    {
        $this->initialiseTest([ResponseStillActiveFixtures::class]);
        $this->doFlowTest(false, true, false);
    }

    public function testFinalQuestionsWithEmptySurveyButEarly(): void
    {
        $this->initialiseTest([ResponseStillActiveFixtures::class, EarlySurveyFixtures::class]);
        $this->doFlowTest(true, true, false);
    }

    public function testFinalQuestionsWithLoadedWithoutUnloaded(): void
    {
        $this->initialiseTest([LoadingActionFixtures::class]);
        $this->doFlowTest(false, false, true);
    }

    public function testFinalQuestionsWithLoadedWithoutUnloadedButEarly(): void
    {
        $this->initialiseTest([LoadingActionFixtures::class, EarlySurveyFixtures::class]);
        $this->doFlowTest(true, false, true);
    }


    public function testFinalQuestionsWithFilledSurvey(): void
    {
        $this->initialiseTest([ActionFixtures::class]);
        $this->doFlowTest(false, false, false);
    }

    public function testFinalQuestionsWithFilledSurveyButEarly(): void
    {
        $this->initialiseTest([ActionFixtures::class, EarlySurveyFixtures::class]);
        $this->doFlowTest(true, false, false);
    }

    public function testFinalQuestionsWithDomesticOnly(): void
    {
        $this->initialiseTest([ResponseDomesticOnlyFixtures::class]);
        $this->doNonResponseTest();
    }

    public function testFinalQuestionsWithCeasedTrading(): void
    {
        $this->initialiseTest([ResponseCeasedTradingFixtures::class]);
        $this->doNonResponseTest();
    }

    public function testFinalQuestionsWithDomesticOnlyButEarly(): void
    {
        $this->initialiseTest([ResponseDomesticOnlyFixtures::class, EarlySurveyFixtures::class]);
        $this->doNonResponseTest();
    }

    public function testFinalQuestionsWithCeasedTradingButEarly(): void
    {
        $this->initialiseTest([ResponseCeasedTradingFixtures::class, EarlySurveyFixtures::class]);
        $this->doNonResponseTest();
    }

    protected function doNonResponseTest(): void
    {
        $this->formTestAction(
            '/international-survey/correspondence-and-business-details',
            'confirmation_submit',
            [new FormTestCase([])]
        );

        $this->checkFinalRouteAndEntityState();
    }

    protected function doFlowTest(
        bool $expectedEarlyResponse,
        bool $expectedEmptySurvey,
        bool $expectedLoadedButNotUnloaded,
    ): void
    {
        $this->pathTestAction('/international-survey');
        $this->clickLinkContaining('Submit survey');

        if ($expectedEarlyResponse) {
            $this->formTestAction(
                '/international-survey/closing-details/early-response',
                'early_response_continue',
                [
                    new FormTestCase([], ['#early_response_is_correct']),
                    new FormTestCase([
                        'early_response[is_correct]' => "true",
                    ]),
                ]
            );
        }

        if ($expectedEmptySurvey) {
            $this->formTestAction(
                '/international-survey/closing-details/empty-survey',
                'reason_empty_survey_continue',
                [
                    new FormTestCase([], ['#reason_empty_survey_reasonForEmptySurvey']),
                    new FormTestCase([
                        'reason_empty_survey[reasonForEmptySurvey]' => 'no-international-work'
                    ]),
                ]
            );
        }

        if ($expectedLoadedButNotUnloaded) {
            $this->formTestAction(
                '/international-survey/closing-details/loading-without-unloading',
                'loading_without_unloading_continue',
                [
                    new FormTestCase([], ['#loading_without_unloading_is_correct']),
                    new FormTestCase([
                        'loading_without_unloading[is_correct]' => 'true'
                    ]),
                ]
            );
        }

        $this->formTestAction(
            '/international-survey/closing-details/confirm',
        'form_continue', [
            new FormTestCase([]),
        ]);

        $this->checkFinalRouteAndEntityState();
    }

    function checkFinalRouteAndEntityState(): void
    {
        $this->pathTestAction('/international-survey/completed');

        $this->callbackTestAction(function(Context $context) {
            $entityManager = $context->getEntityManager();

            /** @var SurveyRepository $repo */
            $repo = $entityManager->getRepository(Survey::class);
            $entityManager->clear();
            $surveys = $repo->findAll();

            $test = $context->getTestCase();
            $test->assertCount(1, $surveys, 'Expected a single surveyResponse to be in the database');

            $survey = $surveys[0];
            $test->assertEquals(SurveyStateInterface::STATE_CLOSED, $survey->getState());
        });
    }
}
