<?php

namespace App\Tests\NewFunctional\Wizard\International\TripDistanceDisabled;

use App\Entity\International\Vehicle;
use App\Tests\DataFixtures\International\TripDistanceDisabled\VehicleFixtures;
use App\Tests\DataFixtures\International\TripDistanceDisabled\VehicleRigidFixtures;
use App\Tests\DataFixtures\RoRo\RouteAndPortFixtures;
use App\Tests\NewFunctional\Wizard\Action\PathTestAction;
use App\Tests\NewFunctional\Wizard\Form\FormTestCase;

class TripTest extends AbstractTripTest
{
    public function testArticulated(): void
    {
        $this->initialiseTest([VehicleFixtures::class, RouteAndPortFixtures::class]);

        $this->clickLinkContaining('View', 1); // N.B. 0th link is "Correspondence / business details"
        $this->clickLinkContaining('Add trip');

        $this->originDestinationDatesAndPorts();
        $this->keptSameTrailer();
        $this->keptSameBodyType();
        $this->countries();

        $this->pathTestAction('#^/international-survey/trips/[^/]+$#', [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);

        $expectedData = $this->getExpectedData();
        $this->assertDashboardMatchesData($expectedData);
        $this->assertDatabaseMatchesData($expectedData);
    }

    public function testArticulatedWithBodySwap(): void
    {
        $this->initialiseTest([VehicleFixtures::class, RouteAndPortFixtures::class]);

        $this->clickLinkContaining('View', 1);
        $this->clickLinkContaining('Add trip');

        $this->originDestinationDatesAndPorts();
        $this->keptSameTrailer();
        $this->changedBodyType('liquid');
        $this->weight('28000', '24000');
        $this->countries();

        $this->pathTestAction('#^/international-survey/trips/[^/]+$#', [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);

        $expectedData = $this->getExpectedData();
        $expectedData['isChangedBodyType'] = true;
        $expectedData['bodyType'] = 'liquid';
        $expectedData['grossWeight'] = 28000;
        $expectedData['carryingCapacity'] = 24000;

        $this->assertDashboardMatchesData($expectedData);
        $this->assertDatabaseMatchesData($expectedData);
    }

    public function testArticulatedWithTrailerAndBodySwap(): void
    {
        $this->initialiseTest([VehicleFixtures::class, RouteAndPortFixtures::class]);

        $this->clickLinkContaining('View', 1); // N.B. 0th link is "Correspondence / business details"
        $this->clickLinkContaining('Add trip');

        $this->originDestinationDatesAndPorts();
        $this->swappedTrailer('332');
        $this->changedBodyType('box');
        $this->weight();
        $this->countries();

        $this->pathTestAction('#^/international-survey/trips/[^/]+$#', [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);

        $expectedData = $this->getExpectedData();
        $expectedData['isSwappedTrailer'] = true;
        $expectedData['isChangedBodyType'] = true;
        $expectedData['bodyType'] = 'box';
        $expectedData['axleConfiguration'] = 332;

        $this->assertDashboardMatchesData($expectedData);
        $this->assertDatabaseMatchesData($expectedData);
    }

    public function testRigid(): void
    {
        $this->initialiseTest([VehicleRigidFixtures::class, RouteAndPortFixtures::class]);

        $this->clickLinkContaining('View', 1);
        $this->clickLinkContaining('Add trip');

        $this->originDestinationDatesAndPorts();
        $this->keptSameTrailer();
        $this->countries();

        $this->pathTestAction('#^/international-survey/trips/[^/]+$#', [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);

        $expectedData = $this->getExpectedData();

        $this->assertDashboardMatchesData($expectedData, true);
        $this->assertDatabaseMatchesData($expectedData);
    }

    public function testRigidWithTrailerSwap(): void
    {
        $this->initialiseTest([VehicleRigidFixtures::class, RouteAndPortFixtures::class]);

        $this->clickLinkContaining('View', 1);
        $this->clickLinkContaining('Add trip');

        $this->originDestinationDatesAndPorts();
        $this->swappedTrailer('232'); // Becomes a rigid with trailer
        $this->weight(31000,26000);
        $this->countries();

        $this->pathTestAction('#^/international-survey/trips/[^/]+$#', [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);

        $expectedData = $this->getExpectedData();
        $expectedData['isSwappedTrailer'] = true;
        $expectedData['axleConfiguration'] = 232;
        $expectedData['grossWeight'] = 31000;
        $expectedData['carryingCapacity'] = 26000;

        $this->assertDashboardMatchesData($expectedData, true);
        $this->assertDatabaseMatchesData($expectedData);
    }

    public function originDestinationDatesAndPorts(): void
    {
        $this->formTestAction('#^/international-survey/vehicles/[^/]+/add-trip/origin-and-destination$#', 'origin_and_destination_buttons_continue', [
            new FormTestCase([
                'origin_and_destination[origin]' => 'Southampton',
                'origin_and_destination[destination]' => 'Paris',
            ]),
        ], [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);

        $this->formTestAction('#^/international-survey/vehicles/[^/]+/add-trip/dates$#', 'dates_buttons_continue', [
            new FormTestCase([
                'dates[outboundDate][day]' => '5',
                'dates[outboundDate][month]' => '10',
                'dates[outboundDate][year]' => '2024',
                'dates[returnDate][day]' => '8',
                'dates[returnDate][month]' => '10',
                'dates[returnDate][year]' => '2024',
            ]),
        ], [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);

        $this->formTestAction('#^/international-survey/vehicles/[^/]+/add-trip/outbound-ports$#', 'outbound_ports_and_cargo_state_buttons_continue', [
            new FormTestCase([
                'outbound_ports_and_cargo_state[ports]' => '1', // Portfoot - Waspwerp
                'outbound_ports_and_cargo_state[wasAtCapacity]' => 'yes',
                'outbound_ports_and_cargo_state[wasLimitedBy][]' => 'space',
            ]),
        ], [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);

        $this->formTestAction('#^/international-survey/vehicles/[^/]+/add-trip/return-ports$#', 'return_ports_and_cargo_state_buttons_continue', [
            new FormTestCase([
                'return_ports_and_cargo_state[ports]' => '1',
                'return_ports_and_cargo_state[wasAtCapacity]' => 'no',
                'return_ports_and_cargo_state[wasEmpty]' => 'yes',
            ]),
        ], [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);
    }

    protected function keptSameTrailer(): void
    {
        $this->formTestAction('#^/international-survey/vehicles/[^/]+/add-trip/swapped-trailer$#', 'swapped_trailer_buttons_continue', [
            new FormTestCase([
                'swapped_trailer[isSwappedTrailer]' => 'no',
            ]),
        ], [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);
    }

    protected function swappedTrailer(string $newAxleConfiguration): void
    {
        $this->formTestAction('#^/international-survey/vehicles/[^/]+/add-trip/swapped-trailer$#', 'swapped_trailer_buttons_continue', [
            new FormTestCase([
                'swapped_trailer[isSwappedTrailer]' => 'yes',
                'swapped_trailer[axleConfiguration]' => $newAxleConfiguration,
            ]),
        ], [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);
    }

    protected function keptSameBodyType(): void
    {
        $this->formTestAction('#^/international-survey/vehicles/[^/]+/add-trip/body-type$#', 'changed_body_type_buttons_continue', [
            new FormTestCase([
                'changed_body_type[isChangedBodyType]' => 'no',
            ]),
        ], [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);
    }

    protected function changedBodyType(string $newBodyType): void
    {
        $this->formTestAction('#^/international-survey/vehicles/[^/]+/add-trip/body-type$#', 'changed_body_type_buttons_continue', [
            new FormTestCase([
                'changed_body_type[isChangedBodyType]' => 'yes',
                'changed_body_type[bodyType]' => $newBodyType,
            ]),
        ], [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);
    }

    public function weight(string $grossWeight='32000', string $carryingCapacity='25000'): void
    {
        $this->formTestAction('#^/international-survey/vehicles/[^/]+/add-trip/vehicle-weights$#', 'vehicle_weight_buttons_continue', [
            new FormTestCase([
                'vehicle_weight[carryingCapacity]' => $carryingCapacity,
                'vehicle_weight[grossWeight]' => $grossWeight,
            ]),
        ], [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);
    }

    protected function countries(): void
    {
        $this->formTestAction('#^/international-survey/vehicles/[^/]+/add-trip/countries-transitted$#', 'countries_transitted_buttons_continue', [
            new FormTestCase([
                'countries_transitted[countriesTransitted][]' => 'FR',
                'countries_transitted[countriesTransittedOther]' => 'Belgium',
            ]),
        ], [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);
    }

    public function getExpectedData(): array
    {
        return [
            'outboundDate' => new \DateTime('2024-10-05'),
            'returnDate' => new \DateTime('2024-10-08'),
            'isChangedBodyType' => false,
            'isSwappedTrailer' => false,
            'outboundUkPort' => 'Portfoot',
            'outboundForeignPort' => 'Waspwerp',
            'returnForeignPort' => 'Roysterdam',
            'returnUkPort' => 'Portfoot',
            'countriesTransitted' => ['FR'],
            'countriesTransittedOther' => 'Belgium',
            'returnWasEmpty' => true,
            'returnWasLimitedBySpace' => null,
            'returnWasLimitedByWeight' => null,
            'outboundWasEmpty' => false,
            'outboundWasLimitedBySpace' => true,
            'outboundWasLimitedByWeight' => false,
            'origin' => 'Southampton',
            'destination' => 'Paris',
        ];
    }
}
