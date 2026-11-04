<?php

namespace App\Tests\NewFunctional\Wizard\International;

use App\Features;
use App\Tests\DataFixtures\International\VehicleFixtures;
use App\Tests\DataFixtures\RoRo\RouteAndPortFixtures;
use App\Tests\NewFunctional\Wizard\Action\PathTestAction;
use App\Tests\NewFunctional\Wizard\Form\FormTestCase;
use Ghost\GovUkCoreBundle\Features as CoreFeatures;

/**
 * Trip origin/destination with the IRHS postcode changeover enabled (no changeover date → every survey is postcode-only).
 */
class TripPostcodeTest extends AbstractTripTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->setFeatures(Features::IRHS_POSTCODE_CHANGEOVER, Features::OUTWARD_CODE_CHECK);
        parent::setUp();
    }

    #[\Override]
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->setFeatures();
    }

    private function setFeatures(string ...$features): void
    {
        $_ENV['APP_FEATURES'] = implode(',', $features);
        (new \ReflectionProperty(CoreFeatures::class, 'enabledFeatures'))->setValue(null, []);
    }

    private function getRejectedThenAcceptedTestCases(string $origin, string $destination): array
    {
        return [
            new FormTestCase([
                'origin_and_destination[origin]' => 'Southampton',
                'origin_and_destination[destination]' => 'Paris',
            ], ['#origin_and_destination_origin', '#origin_and_destination_destination']),
            new FormTestCase([
                'origin_and_destination[origin]' => 'SO14 0YG',
                'origin_and_destination[destination]' => 'ZZ1 1AA', // Well-formed, but not a known UK district
            ], ['#origin_and_destination_destination']),
            new FormTestCase([
                'origin_and_destination[origin]' => $origin,
                'origin_and_destination[destination]' => $destination,
            ]),
        ];
    }

    public function testAddTrip(): void
    {
        $this->initialiseTest([VehicleFixtures::class, RouteAndPortFixtures::class]);

        $this->clickLinkContaining('View', 1); // N.B. 0th link is "Correspondence / business details"
        $this->clickLinkContaining('Add trip');

        $this->formTestAction(
            '#^/international-survey/vehicles/[^/]+/add-trip/origin-and-destination$#',
            'origin_and_destination_button_group_continue',
            $this->getRejectedThenAcceptedTestCases('SO14 0YG', 'BT7 1NN'),
            [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]
        );

        $this->pathTestAction('#^/international-survey/vehicles/[^/]+/add-trip/dates$#', [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);
    }

    public function testEditTrip(): void
    {
        $this->performChangeTest(0, function (array &$expectedData) {
            $this->formTestAction(
                '#^/international-survey/trips/[a-f0-9-]+/edit/origin-and-destination$#',
                'origin_and_destination_button_group_continue',
                $this->getRejectedThenAcceptedTestCases('PO19 1AA', 'BT7 1NN'),
                [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]
            );

            $expectedData['origin'] = 'PO19 1AA';
            $expectedData['destination'] = 'BT7 1NN';
        });
    }
}
