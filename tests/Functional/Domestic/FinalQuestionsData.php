<?php

namespace App\Tests\Functional\Domestic;

use App\Tests\Functional\Wizard\FormTestCase;
use App\Tests\Functional\Wizard\WizardEndTestCase;
use App\Tests\Functional\Wizard\WizardStepTestCase;

class FinalQuestionsData
{
    public const STATE_EMPTY = 'empty';
    public const STATE_PARTIAL = 'partial';
    public const STATE_FILLED = 'filled';

    public static function finalQuestionsData(string $surveyState): array
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
                                'value' => '30',
                                'unit' => 'litres',
                            ],
                        ],
                    ]),
                ]),
            ]);
        }

        // It is currently valid to leave ALL of these forms empty
        $data = array_merge($data, [
            new WizardStepTestCase("Driver vacancies", "drivers_and_vacancies_continue", [
                new FormTestCase([
                    'drivers_and_vacancies' => [
                        'numberOfDriversEmployed' => '100',
                        'hasVacancies' => 'yes',
                        'vacancy_details' => [
                            'numberOfDriverVacancies' => '10',
                            'reasonsForDriverVacancies' => [
                                1 => 'covid',
                                4 => 'new-work',
                            ],
                        ],
                        'numberOfDriversThatHaveLeft' => '5',
                    ],
                ]),
            ]),
            new WizardStepTestCase("HGVs and deliveries", "deliveries_continue", [
                new FormTestCase([
                    'deliveries' => [
                        'numberOfLorriesOperated' => '10',
                        'numberOfParkedLorries' => '2',
                        'hasMissedDeliveries' => 'yes',
                        'numberOfMissedDeliveries' => '2',
                    ],
                ]),
            ]),
            new WizardStepTestCase("Driver wages", "wages_continue", [
                new FormTestCase([
                    'wages' => [
                        'haveWagesIncreased' => 'yes',
                        'increased_wages_yes' => [
                            'averageWageIncrease' => [
                                'value' => '10',
                                'unit' => 'percentage',
                            ],
                            'wageIncreasePeriod' => 'weekly',
                            'reasonsForWageIncrease' => [
                                0 => 'planned',
                                2 => 'attract-new',
                            ],
                        ],
                    ],
                ]),
            ]),
            new WizardStepTestCase("Driver incentives", "bonuses_continue", [
                new FormTestCase([
                    'bonuses' => [
                        'hasPaidBonus' => 'yes',
                        'paid_bonus_yes' => [
                            'averageBonus' => '10',
                        ],
                    ],
                ]),
            ]),
        ]);

        return array_merge($data, [
            new WizardStepTestCase("Submit your survey", "form_continue", [
                new FormTestCase([])
            ]),
            new WizardEndTestCase("Survey complete"),
        ]);
    }
}