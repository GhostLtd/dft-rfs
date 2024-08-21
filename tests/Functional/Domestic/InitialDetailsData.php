<?php

namespace App\Tests\Functional\Domestic;

use App\Entity\Domestic\SurveyResponse;
use App\Tests\Functional\Wizard\FormTestCase;
use App\Tests\Functional\Wizard\WizardEndUrlTestCase;
use App\Tests\Functional\Wizard\WizardStepUrlTestCase;

class InitialDetailsData
{
    protected static string $baseUrl = "/domestic-survey/initial-details";
    
    public static function initialDetailsData(string $inPossessionAnswer): array
    {
        $baseData = [
            new WizardStepUrlTestCase(self::$baseUrl."/introduction", "form_continue", [
                new FormTestCase([])
            ]),
            new WizardStepUrlTestCase(self::$baseUrl."/contact-details", "contact_details_continue", [
                new FormTestCase(
                    [],
                    ['#contact_details_contactBusinessName', "#contact_details_contactName", "#contact_details_contactTelephone", "#contact_details_contactEmail"]
                ),
                new FormTestCase([
                    'contact_details' => [
                        'contactBusinessName' => 'Acme Company Ltd.',
                        'contactName' => 'Mark',
                        'contactEmail' => 'mark@example.com',
                    ],
                ]),
            ]),
        ];

        if ($inPossessionAnswer === SurveyResponse::IN_POSSESSION_YES) {
            $baseData[] = new WizardStepUrlTestCase(self::$baseUrl . "/in-possession", "in_possession_of_vehicle_continue", [
                new FormTestCase([], ["#in_possession_of_vehicle_isInPossessionOfVehicle"]),
                new FormTestCase([
                    'in_possession_of_vehicle' => [
                        'isInPossessionOfVehicle' => $inPossessionAnswer,
                    ]
                ], ['#in_possession_of_vehicle_isExemptVehicleType']),
                new FormTestCase([
                    'in_possession_of_vehicle' => [
                        'isInPossessionOfVehicle' => $inPossessionAnswer,
                        'isExemptVehicleType' => strval(array_search(SurveyResponse::IS_EXEMPT_NO, array_values(SurveyResponse::IS_EXEMPT_CHOICES))),
                    ],
                ]),
            ]);
        } else {
            $baseData[] = new WizardStepUrlTestCase(self::$baseUrl . "/in-possession", "in_possession_of_vehicle_continue", [
                new FormTestCase([], ["#in_possession_of_vehicle_isInPossessionOfVehicle"]),
                new FormTestCase([
                    'in_possession_of_vehicle' => [
                        'isInPossessionOfVehicle' => $inPossessionAnswer,
                    ]
                ]),
            ]);
        }

        $endData = [
            new WizardEndUrlTestCase('/domestic-survey')
        ];
        return match ($inPossessionAnswer) {
            SurveyResponse::IN_POSSESSION_YES => array_merge($baseData, $endData),
            SurveyResponse::IN_POSSESSION_ON_HIRE => array_merge($baseData, self::onHireData(), $endData),
            SurveyResponse::IN_POSSESSION_SCRAPPED_OR_STOLEN => array_merge($baseData, self::scrappedData(), $endData),
            SurveyResponse::IN_POSSESSION_SOLD => array_merge($baseData, self::soldData(), $endData),
            default => throw new \RuntimeException('Invalid "inPossessionAnswer" parameter'),
        };
    }

    protected static function onHireData(): array
    {
        return [
            new WizardStepUrlTestCase(self::$baseUrl."/hiree-details", "hiree_details_continue", [
                new FormTestCase([], [
                    "#hiree_details_hireeTelephone",
                    "#hiree_details_hireeEmail",
                    "#hiree_details_hireeName",
                    '#hiree_details_hireeAddress',
                ]),
                new FormTestCase([
                    'hiree_details' => [
                        'hireeName' => "Mark",
                        'hireeEmail' => "mark@example.com",
                        'hireeAddress' => [
                            'line1' => '123 Road Road',
                            'postcode' => 'AB10 1AB',
                        ],
                    ],
                ]),
            ]),
        ];
    }

    protected static function scrappedData(): array
    {
        return [
            new WizardStepUrlTestCase(self::$baseUrl."/scrapped-details","scrapped_details_continue", [
                new FormTestCase([], [
                    "#scrapped_details_scrappedDate",
                ]),
                new FormTestCase([
                    'scrapped_details' => [
                        'scrappedDate' => [
                            'day' => '12',
                            'month' => '3',
                            'year' => '2021',
                        ],
                    ],
                ]),
            ]),
        ];
    }

    protected static function soldData(): array
    {
        return [
            new WizardStepUrlTestCase(self::$baseUrl."/sold-details","sold_details_continue", [
                new FormTestCase([], [
                    "#sold_details_soldDate",
                    "#sold_details_newOwnerName",
                    "#sold_details_newOwnerEmail",
                    "#sold_details_newOwnerTelephone",
                    "#sold_details_newOwnerAddress",
                ]),
                new FormTestCase([
                    'sold_details' => [
                        'soldDate' => [
                            'day' => '12',
                            'month' => '3',
                            'year' => '2021',
                        ],
                        'newOwnerName' => 'Mark',
                        'newOwnerEmail' => 'mark@example.com',
                        'newOwnerAddress' => [
                            'line1' => '123 Road Road',
                            'postcode' => 'AB10 1AB',
                        ],
                    ],
                ]),
            ]),
        ];
    }
}