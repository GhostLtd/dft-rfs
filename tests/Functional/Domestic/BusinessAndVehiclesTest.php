<?php

namespace App\Tests\Functional\Domestic;

use App\Entity\Domestic\SurveyResponse;
use App\Tests\DataFixtures\Domestic\ResponseFixtures;
use App\Tests\Functional\AbstractWizardTest;
use App\Tests\Functional\Wizard\FormTestCase;
use App\Tests\Functional\Wizard\WizardEndUrlTestCase;
use App\Tests\Functional\Wizard\WizardStepUrlTestCase;

class BusinessAndVehiclesTest extends AbstractWizardTest
{
    protected string $baseUrl = "/domestic-survey/vehicle-and-business-details";

    protected function businessAndVehiclesData(string $trailerConfiguration, string $axleConfiguration): array
    {
        return [
            new WizardStepUrlTestCase("{$this->baseUrl}/business-details", "business_details_continue", [
                new FormTestCase([], [
                    "#business_details_numberOfEmployees",
                    "#business_details_businessNature",
                    "#business_details_operationType",
                ]),
                new FormTestCase([
                    "business_details" => [
                        "numberOfEmployees" => SurveyResponse::EMPLOYEES_10_TO_49,
                        "businessNature" => "Haulage",
                        "operationType" => "for-hire-and-reward",
                    ]
                ]),
            ]),
            new WizardStepUrlTestCase("{$this->baseUrl}/vehicle-trailer-configuration","vehicle_trailer_configuration_continue", [
                new FormTestCase([], [
                    "#vehicle_trailer_configuration_trailerConfiguration",
                ]),
                new FormTestCase([
                    "vehicle_trailer_configuration" => [
                        "trailerConfiguration" => $trailerConfiguration,
                    ],
                ]),
            ]),
            new WizardStepUrlTestCase("{$this->baseUrl}/vehicle-axle-configuration","vehicle_axle_configuration_continue", [
                new FormTestCase([], [
                    "#vehicle_axle_configuration_axleConfiguration",
                ]),
                new FormTestCase([
                    "vehicle_axle_configuration" => [
                        "axleConfiguration" => $axleConfiguration,
                    ],
                ]),
            ]),
            new WizardStepUrlTestCase("{$this->baseUrl}/vehicle-body","vehicle_body_continue", [
                new FormTestCase([], [
                    "#vehicle_body_bodyType",
                ]),
                new FormTestCase([
                    "vehicle_body" => [
                        "bodyType" => "flat-drop",
                    ],
                ]),
            ]),
            new WizardStepUrlTestCase("{$this->baseUrl}/vehicle-weights","vehicle_weights_continue", [
                new FormTestCase([], [
                    "#vehicle_weights_grossWeight",
                    "#vehicle_weights_carryingCapacity",
                ]),
                // Triggering an error like this on the last page of the wizard was causing errors in DataConsistencyListener
                // as it was then receiving a proxy survey which wasn't being correctly initialized when checked.
                new FormTestCase([
                    "vehicle_weights" => [
                        "grossWeight" => "30000",
                    ],
                ], [
                    "#vehicle_weights_carryingCapacity",
                ]),
                new FormTestCase([
                    "vehicle_weights" => [
                        "grossWeight" => "30000",
                        "carryingCapacity" => "24000",
                    ],
                ]),
            ]),
            new WizardEndUrlTestCase('/domestic-survey'),
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
        $browser->request('GET', "{$this->baseUrl}/business-details");

        $this->doWizardTest($browser, $wizardData);
    }
}