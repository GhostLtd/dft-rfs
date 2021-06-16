<?php

namespace App\Tests\Functional\Domestic;

use App\Tests\DataFixtures\SurveyFixtures;
use App\Tests\Functional\AbstractWizardTest;
use App\Tests\Functional\Wizard\FormTestCase;
use App\Tests\Functional\Wizard\WizardStepTestCase;

class InitialDetailsTest extends AbstractWizardTest
{
    protected function initialDetailsData(string $inPossessionAnswer): array
    {
        return [
            new WizardStepTestCase("Welcome to your Domestic Road Haulage Survey", "form_continue", [
                new FormTestCase([])
            ]),
            new WizardStepTestCase("Contact details", "contact_details_continue", [
                new FormTestCase(
                    [],
                    ["#contact_details_contactName", "#contact_details_contactTelephone", "#contact_details_contactEmail"]
                ),
                new FormTestCase([
                    'contact_details' => [
                        'contactName' => 'Mark',
                        'contactEmail' => 'mark@example.com',
                    ],
                ]),
            ]),
            new WizardStepTestCase("Will you be in possession of the vehicle during your survey week?", "in_possession_of_vehicle_continue", [
                new FormTestCase([], ["#in_possession_of_vehicle_isInPossessionOfVehicle"]),
                new FormTestCase([
                    'in_possession_of_vehicle' => [
                        'isInPossessionOfVehicle' => $inPossessionAnswer,
                    ]
                ]),
            ]),
        ];
    }

    protected function onHireData(): array
    {
        return [
            new WizardStepTestCase("Hiree details", "hiree_details_continue", [
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

    protected function scrappedData(): array
    {
        return [
            new WizardStepTestCase("When was the vehicle scrapped or stolen?","scrapped_details_continue", [
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

    protected function soldData(): array
    {
        return [
            new WizardStepTestCase("Details of the sale","sold_details_continue", [
                new FormTestCase([], [
                    "#sold_details_soldDate",
                    "#sold_details_newOwnerName",
                    "#sold_details_newOwnerEmail",
                    '#sold_details_newOwnerAddress_line1',
                    '#sold_details_newOwnerAddress_postcode',
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