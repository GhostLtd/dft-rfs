<?php

namespace App\Tests\Functional\Domestic;

use App\Entity\Domestic\SurveyResponse;
use App\Entity\Domestic\Vehicle as DomesticVehicle;
use App\Entity\SurveyResponse as BaseSurveyResponse;
use App\Entity\Vehicle;
use App\Tests\DataFixtures\Domestic\VehicleFixtures;
use App\Tests\Functional\AbstractWizardTest;
use App\Tests\Functional\Wizard\CallbackSingleEntityDatabaseTestCase;
use App\Tests\Functional\Wizard\FormTestCase;
use App\Tests\Functional\Wizard\WizardStepUrlTestCase;

class BusinessAndVehiclesEditTest extends AbstractWizardTest
{
    protected string $baseUrl = "/domestic-survey/vehicle-and-business-details";

    public function wizardData(): array
    {
        return [
            'Change business details' => [
                '/change-business-details',
                [VehicleFixtures::class], // extra fixtures
                [
                    new WizardStepUrlTestCase("{$this->baseUrl}/change-business-details", "business_details_continue", [
                        new FormTestCase([
                            "business_details" => [
                                "businessNature" => null,
                            ]
                        ], [
                            "#business_details_businessNature",
                        ]),
                        new FormTestCase([
                            "business_details" => [
                                "numberOfEmployees" => BaseSurveyResponse::EMPLOYEES_250_TO_499,
                                "businessNature" => "Waste removal",
                                "operationType" => Vehicle::OPERATION_TYPE_FOR_HIRE_AND_REWARD,
                            ]
                        ]),
                    ]),
                    new CallbackSingleEntityDatabaseTestCase(SurveyResponse::class, function(SurveyResponse $response) {
                        $this->assertEquals("Waste removal", $response->getBusinessNature());
                        $this->assertEquals(BaseSurveyResponse::EMPLOYEES_250_TO_499, $response->getNumberOfEmployees());
                        $this->assertEquals(Vehicle::OPERATION_TYPE_FOR_HIRE_AND_REWARD, $response->getVehicle()->getOperationType());
                    })
                ], // Wizard steps
            ],
            'Change vehicle weights' => [
                '/change-vehicle-weights',
                [VehicleFixtures::class], // extra fixtures
                [
                    new WizardStepUrlTestCase("{$this->baseUrl}/change-vehicle-weights", "vehicle_weights_continue", [
                        new FormTestCase([
                            "vehicle_weights" => [
                                "grossWeight" => null,
                                "carryingCapacity" => null,
                            ]
                        ], [
                            "#vehicle_weights_grossWeight",
                            "#vehicle_weights_carryingCapacity",
                        ]),
                        new FormTestCase([ // Carrying capacity > Gross weight
                            "vehicle_weights" => [
                                "grossWeight" => "30000",
                                "carryingCapacity" => "40000",
                            ]
                        ], [
                            "#vehicle_weights_carryingCapacity",
                        ]),
                        new FormTestCase([
                            "vehicle_weights" => [
                                "grossWeight" => "31987",
                                "carryingCapacity" => "21987",
                            ]
                        ]),
                    ]),
                    new CallbackSingleEntityDatabaseTestCase(DomesticVehicle::class, function(DomesticVehicle $vehicle) {
                        $this->assertEquals("31987", $vehicle->getGrossWeight());
                        $this->assertEquals("21987", $vehicle->getCarryingCapacity());
                    })
                ], // Wizard steps
            ],
            'Change trailer config' => [
                '/change-vehicle-trailer-configuration',
                [VehicleFixtures::class], // extra fixtures
                [
                    new WizardStepUrlTestCase("{$this->baseUrl}/change-vehicle-trailer-configuration", "vehicle_trailer_configuration_continue", [
                        new FormTestCase([
                            "vehicle_trailer_configuration" => [
                                "trailerConfiguration" => "300",
                            ]
                        ]),
                        new FormTestCase([
                            "vehicle_axle_configuration" => [
                                "axleConfiguration" => "321",
                            ]
                        ], [], "vehicle_axle_configuration_continue"),
                        new FormTestCase([
                            "vehicle_body" => [
                                "bodyType" => Vehicle::BODY_TYPE_CAR,
                            ]
                        ], [], "vehicle_body_continue"),
                    ]),
                    new CallbackSingleEntityDatabaseTestCase(DomesticVehicle::class, function(DomesticVehicle $vehicle) {
                        $this->assertEquals(300, $vehicle->getTrailerConfiguration());
                        $this->assertEquals(321, $vehicle->getAxleConfiguration());
                        $this->assertEquals(Vehicle::BODY_TYPE_CAR, $vehicle->getBodyType());
                    })
                ], // Wizard steps
            ],
        ];
    }

    /**
     * @dataProvider wizardData
     */
    public function testBusinessAndVehiclesEditWizards($startRelativeUrl, $fixtures, $wizardData)
    {
        $browser = $this->getBrowserLoadFixturesAndLogin($fixtures);
        $browser->request('GET', "{$this->baseUrl}{$startRelativeUrl}");

        $this->doWizardTest($browser, $wizardData);
    }
}