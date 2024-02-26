<?php

namespace App\Tests\NewFunctional\Wizard\International;

use App\Tests\NewFunctional\Wizard\Action\PathTestAction;
use App\Tests\NewFunctional\Wizard\Form\FormTestCase;

class VehicleChangeTest extends AbstractVehicleTest
{
    public function testChangeRegistration(): void
    {
        $this->performChangeTest(0, function(array &$expectedData) {
            $options = [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true];

            $this->formTestAction('#^\/international-survey\/vehicles\/[a-f0-9-]+\/change-vehicle-registration$#', 'vehicle_details_continue', [
                new FormTestCase([
                    'vehicle_details[registrationMark]' => 'BB02 DCE',
                    'vehicle_details[operationType]' => 'for-hire-and-reward',
                ]),
            ], $options);

            $expectedData['registrationMark'] = 'BB02DCE';
            $expectedData['operationType'] = 'for-hire-and-reward';
        });
    }

    public function testChangeTrailerConfig(): void
    {
        $this->performChangeTest(2, function(array &$expectedData) {
            $options = [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true];

            $this->formTestAction('#^\/international-survey\/vehicles\/[a-f0-9-]+\/change-trailer-configuration$#', 'vehicle_trailer_configuration_continue', [
                new FormTestCase([
                    'vehicle_trailer_configuration[trailerConfiguration]' => '100',
                ]),
            ], $options);

            $this->formTestAction('#^\/international-survey\/vehicles\/[a-f0-9-]+\/change-axle-configuration$#', 'vehicle_axle_configuration_continue', [
                new FormTestCase([
                    'vehicle_axle_configuration[axleConfiguration]' => '140',
                ]),
            ], $options);

            $expectedData['axleConfiguration'] = 140;
            $expectedData['trailerConfiguration'] = 100;
        });
    }

    public function testChangeBodyType(): void
    {
        $this->performChangeTest(3, function(array &$expectedData) {
            $options = [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true];

            $this->formTestAction('#^\/international-survey\/vehicles\/[a-f0-9-]+\/change-vehicle-body$#', 'vehicle_body_continue', [
                new FormTestCase([
                    'vehicle_body[bodyType]' => 'tipper',
                ]),
            ], $options);

            $expectedData['bodyType'] = 'tipper';
        });
    }

    public function testVehicleWeight(): void
    {
        $this->performChangeTest(4, function(array &$expectedData) {
            $options = [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true];

            $this->formTestAction('#^\/international-survey\/vehicles\/[a-f0-9-]+\/change-vehicle-weight$#', 'vehicle_weight_continue', [
                new FormTestCase([
                    'vehicle_weight[grossWeight]' => '44444',
                    'vehicle_weight[carryingCapacity]' => '33333',
                ]),
            ], $options);

            $expectedData['grossWeight'] = 44444;
            $expectedData['carryingCapacity'] = 33333;
        });
    }
}
