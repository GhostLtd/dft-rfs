<?php

namespace App\Tests\Functional\Domestic;

use App\Tests\Functional\Wizard\FormTestCase;
use App\Tests\Functional\Wizard\WizardEndUrlTestCase;
use App\Tests\Functional\Wizard\WizardStepUrlTestCase;

class FinalQuestionsData
{
    public const STATE_EMPTY = 'empty';
    public const STATE_PARTIAL = 'partial';
    public const STATE_FILLED = 'filled';

    private static string $baseUrl = "/domestic-survey/closing-details";

    public static function finalQuestionsData(string $surveyState): array
    {
        $data = [
            new WizardStepUrlTestCase(self::$baseUrl . "/start", "form_continue", [
                new FormTestCase([])
            ]),
        ];

        if (in_array($surveyState, [self::STATE_EMPTY, self::STATE_PARTIAL])) {
            $data = array_merge($data, [
                new WizardStepUrlTestCase(self::$baseUrl . "/missing-days", "missing_days_continue", [
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
                new WizardStepUrlTestCase(self::$baseUrl . "/empty-survey", "reason_empty_survey_continue", [
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
                new WizardStepUrlTestCase(self::$baseUrl . "/vehicle-fuel", "vehicle_fuel_continue", [
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
            new WizardStepUrlTestCase(self::$baseUrl . "/da-drivers", "drivers_and_vacancies_continue", [
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
            new WizardStepUrlTestCase(self::$baseUrl . "/da-deliveries", "deliveries_continue", [
                new FormTestCase([
                    'deliveries' => [
                        'numberOfLorriesOperated' => '10',
                        'numberOfParkedLorries' => '2',
                        'hasMissedDeliveries' => 'yes',
                        'numberOfMissedDeliveries' => '2',
                    ],
                ]),
            ]),
            new WizardStepUrlTestCase(self::$baseUrl . "/da-wages", "wages_continue", [
                new FormTestCase([
                    'wages' => [
                        'haveWagesIncreased' => 'yes',
                        'increased_wages_yes' => [
                            'averageWageIncrease' => '10.23',
                            'wageIncreasePeriod' => 'weekly',
                            'reasonsForWageIncrease' => [
                                0 => 'planned',
                                2 => 'attract-new',
                            ],
                        ],
                    ],
                ]),
            ]),
            new WizardStepUrlTestCase(self::$baseUrl . "/da-bonuses", "bonuses_continue", [
                new FormTestCase([
                    'bonuses' => [
                        'hasPaidBonus' => 'yes',
                        'paid_bonus_yes' => [
                            'averageBonus' => '11.23',
                        ],
                    ],
                ]),
            ]),
        ]);

        return array_merge($data, [
            new WizardStepUrlTestCase(self::$baseUrl . "/confirm", "form_continue", [
                new FormTestCase([])
            ]),
            new WizardEndUrlTestCase("/domestic-survey/completed"),
        ]);
    }
}