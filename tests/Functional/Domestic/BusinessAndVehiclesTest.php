<?php

namespace App\Tests\Functional\Domestic;

use App\Tests\DataFixtures\ResponseFixtures;
use App\Tests\Functional\AbstractWizardTest;
use App\Tests\Functional\Wizard\FormTestCase;
use App\Tests\Functional\Wizard\WizardStepTestCase;

class BusinessAndVehiclesTest extends AbstractWizardTest
{
    protected function businessAndVehiclesData(string $trailerConfiguration, string $axleConfiguration): array
    {
        return [
            new WizardStepTestCase("Business Details", "business_details_continue", [
                new FormTestCase([], [
                    "#business_details_numberOfEmployees",
                    "#business_details_businessNature",
                    "#business_details_operationType",
                ]),
                new FormTestCase([
                    "business_details" => [
                        "numberOfEmployees" => "10-49",
                        "businessNature" => "Haulage",
                        "operationType" => "for-hire-and-reward",
                    ]
                ]),
            ]),
            new WizardStepTestCase("Vehicle type","vehicle_trailer_configuration_continue", [
                new FormTestCase([], [
                    "#vehicle_trailer_configuration_trailerConfiguration",
                ]),
                new FormTestCase([
                    "vehicle_trailer_configuration" => [
                        "trailerConfiguration" => $trailerConfiguration,
                    ],
                ]),
            ]),
            new WizardStepTestCase("Vehicle axle configuration","vehicle_axle_configuration_continue", [
                new FormTestCase([], [
                    "#vehicle_axle_configuration_axleConfiguration",
                ]),
                new FormTestCase([
                    "vehicle_axle_configuration" => [
                        "axleConfiguration" => $axleConfiguration,
                    ],
                ]),
            ]),
            new WizardStepTestCase("Vehicle body type","vehicle_body_continue", [
                new FormTestCase([], [
                    "#vehicle_body_bodyType",
                ]),
                new FormTestCase([
                    "vehicle_body" => [
                        "bodyType" => "flat-drop",
                    ],
                ]),
            ]),
            new WizardStepTestCase("Vehicle weights","vehicle_weights_continue", [
                new FormTestCase([], [
                    "#vehicle_weights_grossWeight",
                    "#vehicle_weights_carryingCapacity",
                ]),
                new FormTestCase([
                    "vehicle_weights" => [
                        "grossWeight" => "30000",
                        "carryingCapacity" => "24000",
                    ],
                ]),
            ]),
        ];
    }

    public function wizardData(): array
    {
        return [
            'Articulated' => [$this->businessAndVehiclesData("300", "321")],
            'Rigid' => [$this->businessAndVehiclesData("100", "130")],
            'Rigid w/trailer' => [$this->businessAndVehiclesData("200", "223")],
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