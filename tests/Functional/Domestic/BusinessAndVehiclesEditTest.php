<?php

namespace App\Tests\Functional\Domestic;

use App\Entity\Domestic\SurveyResponse;
use App\Entity\Vehicle;
use App\Tests\DataFixtures\Domestic\ResponseFixtures;
use App\Tests\DataFixtures\Domestic\VehicleFixtures;
use App\Tests\Functional\AbstractWizardTest;
use App\Tests\Functional\Wizard\FormTestCase;
use App\Tests\Functional\Wizard\WizardStepUrlTestCase;

class BusinessAndVehiclesEditTest extends AbstractWizardTest
{
    protected string $baseUrl = "/domestic-survey/vehicle-and-business-details";

    public function wizardData(): array
    {
        return [
            'my test' => [
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
                                "numberOfEmployees" => SurveyResponse::EMPLOYEES_10_TO_49,
                                "businessNature" => "Haulage",
                                "operationType" => Vehicle::OPERATION_TYPE_FOR_HIRE_AND_REWARD,
                            ]
                        ]),
                    ]),
                ], // Wizard steps
            ]
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