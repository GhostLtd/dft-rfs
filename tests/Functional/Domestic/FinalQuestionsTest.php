<?php

namespace App\Tests\Functional\Domestic;

use App\Tests\DataFixtures\DayFullOfSummaryFixtures;
use App\Tests\DataFixtures\DayStopFixtures;
use App\Tests\DataFixtures\DaySummaryFixtures;
use App\Tests\DataFixtures\VehicleFixtures;
use App\Tests\Functional\AbstractWizardTest;
use App\Tests\Functional\Wizard\FormTestCase;
use App\Tests\Functional\Wizard\WizardEndTestCase;
use App\Tests\Functional\Wizard\WizardStepTestCase;

class FinalQuestionsTest extends AbstractWizardTest
{
    protected const STATE_EMPTY = 'empty';
    protected const STATE_PARTIAL = 'partial';
    protected const STATE_FILLED = 'filled';

    protected function finalQuestionsData(string $surveyState): array
    {
        $data = [
            new WizardStepTestCase("Final questions", "form_continue", [
                new FormTestCase([])
            ]),
        ];

        if (in_array($surveyState, [self::STATE_EMPTY, self::STATE_PARTIAL])) {
            $data = array_merge($data, [
                new WizardStepTestCase("Missing days", "missing_days_continue", [
                    new FormTestCase([], ['#missing_days_missing_days']),
                    new FormTestCase([
                        'missing_days' => [
                            'missing_days' => '1'
                        ]
                    ]),
                ]),
            ]);
        }

        if ($surveyState === self::STATE_EMPTY) {
            $data = array_merge($data, [
                new WizardStepTestCase("Why have you been unable to complete any journeys?", "reason_empty_survey_continue", [
                    new FormTestCase([], ['#reason_empty_survey_reasonForEmptySurvey']),
                    new FormTestCase([
                        'reason_empty_survey' => [
                            'reasonForEmptySurvey' => 'repair'
                        ]
                    ]),
                ]),
            ]);
        } else if (in_array($surveyState, [self::STATE_PARTIAL, self::STATE_FILLED])) {
            $data = array_merge($data, [
                new WizardStepTestCase("Vehicle fuel", "vehicle_fuel_continue", [
                    // It is valid to leave this form empty
                    new FormTestCase([
                        'vehicle_fuel' => [
                            'fuelQuantity' => [
                                'value' => 30,
                                'unit' => 'litres',
                            ],
                        ],
                    ]),
                ]),
            ]);
        }

        return array_merge($data, [
            new WizardStepTestCase("Submit your survey", "form_continue", [
                new FormTestCase([])
            ]),
            new WizardEndTestCase("Survey complete"),
        ]);
    }

    public function testFinalQuestionsWithSomeMissingDaysWizard()
    {
        $browser = $this->getBrowserLoadFixturesAndLogin([DaySummaryFixtures::class, DayStopFixtures::class]);
        $browser->request('GET', '/domestic-survey/closing-details/start');

        $this->doWizardTest($browser, $this->finalQuestionsData(self::STATE_PARTIAL));
    }

    public function testFinalQuestionsWithEmptySurveyWizard()
    {
        $browser = $this->getBrowserLoadFixturesAndLogin([VehicleFixtures::class]);
        $browser->request('GET', '/domestic-survey/closing-details/start');

        $this->doWizardTest($browser, $this->finalQuestionsData(self::STATE_EMPTY));
    }

    public function testFinalQuestionsWithFilledDayWizard()
    {
        $browser = $this->getBrowserLoadFixturesAndLogin([DayFullOfSummaryFixtures::class]);
        $browser->request('GET', '/domestic-survey/closing-details/start');

        $this->doWizardTest($browser, $this->finalQuestionsData(self::STATE_FILLED));
    }
}