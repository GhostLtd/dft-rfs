<?php

namespace App\Tests\Functional;

use App\Tests\DataFixtures\ResponseFixtures;

class CsrgtBusinessAndVehiclesTest extends AbstractWizardTest
{
    protected function businessAndVehiclesData(string $trailerConfiguration, string $axleConfiguration): array
    {
        return [
            [
                "Business Details", // Page title
                "business_details_continue", // Submission button
                [
                    "#business_details_numberOfEmployees",
                    "#business_details_businessNature",
                    "#business_details_operationType",
                ], // Expected errors
                [
                    "business_details" => [
                        "numberOfEmployees" => "10-49",
                        "businessNature" => "Haulage",
                        "operationType" => "for-hire-and-reward",
                    ]
                ], // Data
            ],
            [
                "Vehicle type",
                "vehicle_trailer_configuration_continue",
                [
                    "#vehicle_trailer_configuration_trailerConfiguration",
                ],
                [
                    "vehicle_trailer_configuration" => [
                        "trailerConfiguration" => $trailerConfiguration,
                    ],
                ],
            ],
            [
                "Vehicle axle configuration",
                "vehicle_axle_configuration_continue",
                [
                    "#vehicle_axle_configuration_axleConfiguration",
                ],
                [
                    "vehicle_axle_configuration" => [
                        "axleConfiguration" => $axleConfiguration,
                    ],
                ],
            ],
            [
                "Vehicle body type",
                "vehicle_body_continue",
                [
                    "#vehicle_body_bodyType",
                ],
                [
                    "vehicle_body" => [
                        "bodyType" => "flat-drop",
                    ],
                ],
            ],
            [
                "Vehicle weights",
                "vehicle_weights_continue",
                [
                    "#vehicle_weights_grossWeight",
                    "#vehicle_weights_carryingCapacity",
                ],
                [
                    "vehicle_weights" => [
                        "grossWeight" => "30000",
                        "carryingCapacity" => "24000",
                    ],
                ],
            ],
        ];
    }

    public function wizardData(): array
    {
        return [
            'Business and vehicles (Articulated)' => [$this->businessAndVehiclesData("300", "321")],
            'Business and vehicles (Rigid)' => [$this->businessAndVehiclesData("100", "130")],
            'Business and vehicles (Rigid w/trailer)' => [$this->businessAndVehiclesData("200", "223")],
        ];
    }

    /**
     * @dataProvider wizardData
     */
    public function testBusinessAndVehiclesWizard($wizardData)
    {
        $browser = $this->getBrowserLoadFixturesAndLogin([ResponseFixtures::class]);
        $browser->request('GET', '/domestic-survey/vehicle-and-business-details/business-details');

        $this->doWizardTest($browser, $wizardData);
    }
}