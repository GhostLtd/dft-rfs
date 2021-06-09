<?php

namespace App\Tests\Functional\Domestic;

use App\Tests\DataFixtures\SurveyFixtures;
use App\Tests\Functional\AbstractWizardTest;

class InitialDetailsTest extends AbstractWizardTest
{
    protected function initialDetailsData(string $inPossessionAnswer): array
    {
        return [
            [
                "Welcome to your Domestic Road Haulage Survey", // Page title
                "form_continue", // Submission button
                [], // Expected errors
                [], // Data
            ],
            [
                "Contact details",
                "contact_details_continue",
                [
                    "#contact_details_contactName",
                    "#contact_details_contactTelephone",
                    "#contact_details_contactEmail",
                ],
                [
                    'contact_details' => [
                        'contactName' => 'Mark',
                        'contactEmail' => 'mark@example.com',
                    ],
                ],
            ],
            [
                "Will you be in possession of the vehicle during your survey week?",
                "in_possession_of_vehicle_continue",
                [
                    "#in_possession_of_vehicle_isInPossessionOfVehicle",
                ],
                [
                    'in_possession_of_vehicle' => [
                        'isInPossessionOfVehicle' => $inPossessionAnswer,
                    ],
                ],
            ]
        ];
    }

    protected function onHireData(): array
    {
        return [
            [
                "Hiree details",
                "hiree_details_continue",
                [
                    "#hiree_details_hireeEmail",
                    "#hiree_details_hireeName",
                    '#hiree_details_hireeAddress_line1',
                    '#hiree_details_hireeAddress_postcode',
                ],
                [
                    'hiree_details' => [
                        'hireeName' => "Mark",
                        'hireeEmail' => "mark@example.com",
                        'hireeAddress' => [
                            'line1' => '123 Road Road',
                            'postcode' => 'AB10 1AB',
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function scrappedData(): array
    {
        return [
            [
                "When was the vehicle scrapped or stolen?",
                "scrapped_details_continue",
                [
                    "#scrapped_details_scrappedDate",
                ],
                [
                    'scrapped_details' => [
                        'scrappedDate' => [
                            'day' => '12',
                            'month' => '3',
                            'year' => '2021',
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function soldData(): array
    {
        return [
            [
                "Details of the sale",
                "sold_details_continue",
                [
                    "#sold_details_soldDate",
                    "#sold_details_newOwnerName",
                    "#sold_details_newOwnerEmail",
                    '#sold_details_newOwnerAddress_line1',
                    '#sold_details_newOwnerAddress_postcode',
                ],
                [
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
                ],
            ],
        ];
    }

    public function wizardData(): array
    {
        return [
            'In possession of vehicle' => [$this->initialDetailsData('yes')],
            'Scrapped or stolen' => [array_merge($this->initialDetailsData('scrapped-or-stolen'), $this->scrappedData())],
            'Sold' => [array_merge($this->initialDetailsData('sold'), $this->soldData())],
            'Vehicle on hire' => [array_merge($this->initialDetailsData('on-hire'), $this->onHireData())],
        ];
    }

    /**
     * @dataProvider wizardData
     */
    public function testInitialDetailsWizard($wizardData)
    {
        $browser = $this->getBrowserLoadFixturesAndLogin([SurveyFixtures::class]);

        $this->doWizardTest($browser, $wizardData);
    }
}