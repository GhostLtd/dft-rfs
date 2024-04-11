<?php

namespace App\Tests\NewFunctional\Wizard\Domestic;

use App\Entity\Domestic\SurveyResponse;
use App\Entity\SurveyStateInterface;
use App\Repository\Domestic\SurveyResponseRepository;
use App\Tests\DataFixtures\Domestic\DaysFullOfSummaryFixtures;
use App\Tests\DataFixtures\Domestic\DayStopFixtures;
use App\Tests\DataFixtures\Domestic\DaySummaryFixtures;
use App\Tests\DataFixtures\Domestic\EarlySurveyFixtures;
use App\Tests\DataFixtures\Domestic\ResponseOnHireButInProgress;
use App\Tests\DataFixtures\Domestic\VehicleFixtures;
use App\Tests\NewFunctional\Wizard\AbstractPasscodeWizardTest;
use App\Tests\NewFunctional\Wizard\Action\Context;
use App\Tests\NewFunctional\Wizard\Form\FormTestCase;

class ClosingDetailsTest extends AbstractPasscodeWizardTest
{


    public function testFinalQuestionsWithSomeMissingDays(): void
    {
        $this->initialiseTest([DaySummaryFixtures::class, DayStopFixtures::class]);
        $this->doFlowTest(false, true,  false,true, false);
    }

    public function testFinalQuestionsWithEmptySurvey(): void
    {
        $this->initialiseTest([VehicleFixtures::class]);
        $this->doFlowTest(false, true, true, false, false);
    }

    public function testFinalQuestionsWithFilledDay(): void
    {
        // We also test the driver-availability flow on this test (no need to do that more than once)
        $this->initialiseTest([DaysFullOfSummaryFixtures::class]);
        $this->doFlowTest(false, false, false, true, true);
    }

    public function testNotInPossession(): void
    {
        $this->initialiseTest([ResponseOnHireButInProgress::class]);

        $this->pathTestAction('/domestic-survey');
        $this->clickLinkContaining('Review survey details');

        $this->formTestAction('/domestic-survey/close-not-in-possession', 'confirm_action_confirm', [
            new FormTestCase([]),
        ]);

        $this->pathTestAction('/domestic-survey/completed');
    }

    // --- Early versions of the above ---
    public function testFinalQuestionsWithSomeMissingDaysButEarly(): void
    {
        $this->initialiseTest([DaySummaryFixtures::class, DayStopFixtures::class, EarlySurveyFixtures::class]);
        $this->doFlowTest(true, true,  false,true, false);
    }

    public function testFinalQuestionsWithEmptySurveyButEarly(): void
    {
        $this->initialiseTest([VehicleFixtures::class, EarlySurveyFixtures::class]);
        $this->doFlowTest(true, true, true, false, false);
    }

    public function testFinalQuestionsWithFilledDayButEarly(): void
    {
        $this->initialiseTest([DaysFullOfSummaryFixtures::class, EarlySurveyFixtures::class]);
        $this->doFlowTest(true, false, false, true, false);
    }

    protected function doFlowTest(
        bool $expectedEarlyResponse,
        bool $expectedMissingDays,
        bool $expectedEmptySurvey,
        bool $expectedFuelQuantity,
        bool $testDriverAvailability,
    ): void
    {
        $this->pathTestAction('/domestic-survey');
        $this->clickLinkContaining('Complete survey week');

        $this->formTestAction(
            '/domestic-survey/closing-details/start',
            'form_continue',
            [new FormTestCase([])]
        );

        if ($expectedEarlyResponse) {
            $this->formTestAction(
                '/domestic-survey/closing-details/early-response',
                'early_response_continue',
                [
                    new FormTestCase([], ['#early_response_is_correct']),
                    new FormTestCase([
                        'early_response[is_correct]' => "true",
                    ]),
                ]
            );
        }

        if ($expectedMissingDays) {
            $this->formTestAction(
                '/domestic-survey/closing-details/missing-days',
                'missing_days_continue',
                [
                    new FormTestCase([], ['#missing_days_missing_days']),
                    new FormTestCase([
                        'missing_days[missing_days]' => '1',
                    ]),
                ]
            );
        }

        if ($expectedEmptySurvey) {
            $this->formTestAction(
                '/domestic-survey/closing-details/empty-survey',
                'reason_empty_survey_continue',
                [
                    new FormTestCase([], ['#reason_empty_survey_reasonForEmptySurvey']),
                    new FormTestCase([
                        'reason_empty_survey[reasonForEmptySurvey]' => 'repair'
                    ]),
                ]
            );
        }

        if ($expectedFuelQuantity) {
             $this->formTestAction(
                 '/domestic-survey/closing-details/vehicle-fuel',
                 'vehicle_fuel_continue',
                 [
                    // It is valid to leave this form empty
                    new FormTestCase([
                        'vehicle_fuel[fuelQuantity][value]' => '30',
                        'vehicle_fuel[fuelQuantity][unit]' => 'litres',
                    ]),
                ]
             );
        }

        // We only really need to test the driver availability questions once (they're a linear flow),
        // and so have the option to skip.
        if (!$testDriverAvailability) {
            $this->pathTestAction('/domestic-survey/closing-details/da-drivers');
            return;
        }

        $this->formTestAction(
            "/domestic-survey/closing-details/da-drivers",
            "drivers_and_vacancies_continue",
            [
                new FormTestCase([
                    'drivers_and_vacancies[numberOfDriversEmployed]' => '100',
                    'drivers_and_vacancies[hasVacancies]' => 'yes',
                    'drivers_and_vacancies[vacancy_details][numberOfDriverVacancies]' => '10',
                    'drivers_and_vacancies[vacancy_details][reasonsForDriverVacancies][]' => ['covid', 'new-work'],
                    'drivers_and_vacancies[numberOfDriversThatHaveLeft]' => '5',
                ]),
            ]
        );

        $this->formTestAction(
            "/domestic-survey/closing-details/da-deliveries",
            "deliveries_continue",
            [
                new FormTestCase([
                    'deliveries[numberOfLorriesOperated]' => '10',
                    'deliveries[numberOfParkedLorries]' => '2',
                    'deliveries[hasMissedDeliveries]' => 'yes',
                    'deliveries[numberOfMissedDeliveries]' => '2',
                ]),
            ]
        );

        $this->formTestAction(
            "/domestic-survey/closing-details/da-wages",
            "wages_continue",
            [
                new FormTestCase([
                    'wages[haveWagesIncreased]' => 'yes',
                    'wages[increased_wages_yes][averageWageIncrease]' => '10.23',
                    'wages[increased_wages_yes][wageIncreasePeriod]' => 'weekly',
                    'wages[increased_wages_yes][reasonsForWageIncrease][]' => ['planned', 'attract-new'],
                ]),
            ]
        );

        $this->formTestAction(
            "/domestic-survey/closing-details/da-bonuses",
        "bonuses_continue",
            [
                new FormTestCase([
                    'bonuses[hasPaidBonus]' => 'yes',
                    'bonuses[paid_bonus_yes][averageBonus]' => '11.23',
                ]),
            ]
        );

        $this->formTestAction(
            '/domestic-survey/closing-details/confirm',
        'form_continue', [
            new FormTestCase([]),
        ]);

        $this->pathTestAction('/domestic-survey/completed');

        $this->callbackTestAction(function(Context $context) use ($expectedEmptySurvey) {
            $entityManager = $context->getEntityManager();

            /** @var SurveyResponseRepository $repo */
            $repo = $entityManager->getRepository(SurveyResponse::class);
            $entityManager->clear();
            $responses = $repo->findAll();

            $test = $context->getTestCase();

            $test->assertCount(1, $responses, 'Expected a single surveyResponse to be in the database');

            $response = $responses[0];
            $survey = $response->getSurvey();
            $vehicle = $response->getVehicle();
            $fuelQuantity = $vehicle->getFuelQuantity();

            $expectedReasonForEmptySurvey = $expectedEmptySurvey ? 'repair' : null;
            $expectedFuelValue = $expectedEmptySurvey ? null : 30;
            $expectedFuelUnit = $expectedEmptySurvey ? null : 'litres';

            $test->assertEquals(SurveyStateInterface::STATE_CLOSED, $survey->getState());
            $test->assertEquals($expectedReasonForEmptySurvey, $response->getReasonForEmptySurvey());
            $test->assertEquals($expectedFuelValue, $fuelQuantity->getValue());
            $test->assertEquals($expectedFuelUnit, $fuelQuantity->getUnit());
        });
    }
}