<?php

namespace App\Tests\NewFunctional\Wizard\International;

use App\Entity\International\Trip;
use App\Tests\DataFixtures\International\VehicleFixtures;
use App\Tests\DataFixtures\International\VehicleRigidFixtures;
use App\Tests\DataFixtures\RoRo\RouteAndPortFixtures;
use App\Tests\NewFunctional\Wizard\AbstractPasscodeWizardTest;
use App\Tests\NewFunctional\Wizard\Action\Context;
use App\Tests\NewFunctional\Wizard\Action\PathTestAction;
use App\Tests\NewFunctional\Wizard\Form\FormTestCase;

class AddTripTest extends AbstractPasscodeWizardTest
{
    public function testArticulated(): void
    {
        $this->initialiseTest([VehicleFixtures::class, RouteAndPortFixtures::class]);

        $this->clickLinkContaining('View', 1); // N.B. 0th link is "Correspondence / business details"
        $this->clickLinkContaining('Add trip');

        $this->originDestinationDatesAndPorts();
        $this->keptSameTrailer();
        $this->keptSameBodyType();
        $this->distanceAndCountries();

        $this->pathTestAction('#^/international-survey/trips/[^/]+$#', [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);
        $this->assertTripInDatabaseCorrect();
    }

    public function testArticulatedWithBodySwap(): void
    {
        $this->initialiseTest([VehicleRigidFixtures::class, RouteAndPortFixtures::class]);

        $this->clickLinkContaining('View', 1);
        $this->clickLinkContaining('Add trip');

        $this->originDestinationDatesAndPorts();
        $this->keptSameTrailer();
        $this->distanceAndCountries();

        $this->pathTestAction('#^/international-survey/trips/[^/]+$#', [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);
        $this->assertTripInDatabaseCorrect();
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
        $this->distanceAndCountries();

        $this->pathTestAction('#^/international-survey/trips/[^/]+$#', [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);
        $this->assertTripInDatabaseCorrect(300, 332, 'box', 32000, 25000);
    }

    public function testRigid(): void
    {
        $this->initialiseTest([VehicleRigidFixtures::class, RouteAndPortFixtures::class]);

        $this->clickLinkContaining('View', 1);
        $this->clickLinkContaining('Add trip');

        $this->originDestinationDatesAndPorts();
        $this->keptSameTrailer();
        $this->distanceAndCountries();

        $this->pathTestAction('#^/international-survey/trips/[^/]+$#', [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);
        $this->assertTripInDatabaseCorrect();
    }

    public function testRigidWithTrailerSwap(): void
    {
        $this->initialiseTest([VehicleRigidFixtures::class, RouteAndPortFixtures::class]);

        $this->clickLinkContaining('View', 1);
        $this->clickLinkContaining('Add trip');

        $this->originDestinationDatesAndPorts();
        $this->swappedTrailer('232'); // Becomes a rigid with trailer
        $this->weight();
        $this->distanceAndCountries();

        $this->pathTestAction('#^/international-survey/trips/[^/]+$#', [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);
        $this->assertTripInDatabaseCorrect(200, 232, null, 32000, 25000);
    }

    protected function assertTripInDatabaseCorrect(
        ?int $trailerConfiguration=null,
        ?int $axleConfiguration=null,
        ?string $bodyType=null,
        ?int $grossWeight=null,
        ?int $carryingCapacity=null
    ): void {
        $this->callbackTestAction(function (Context $context) use ($trailerConfiguration, $axleConfiguration, $bodyType, $grossWeight, $carryingCapacity) {
            $trips = $context->getEntityManager()->getRepository(Trip::class)->findAll();

            $test = $context->getTestCase();
            $test->assertCount(1, $trips, "Expected number of trips in the database to be one");

            $trip = $trips[0];

            $test->assertEquals('Southampton', $trip->getOrigin());
            $test->assertEquals('Paris', $trip->getDestination());
            $test->assertDateEquals(new \DateTime('2021-05-05'), $trip->getOutboundDate());
            $test->assertDateEquals(new \DateTime('2021-05-08'), $trip->getReturnDate());
            $test->assertEquals('Portfoot', $trip->getOutboundUkPort());
            $test->assertEquals('Waspwerp', $trip->getOutboundForeignPort());
            $test->assertEquals('Roysterdam', $trip->getReturnForeignPort());
            $test->assertEquals('Portfoot', $trip->getReturnUkPort());
            $test->assertEquals(false, $trip->getOutboundWasEmpty());
            $test->assertEquals(true, $trip->getOutboundWasLimitedBySpace());
            $test->assertEquals(false, $trip->getOutboundWasLimitedByWeight());
            $test->assertEquals(true, $trip->getReturnWasEmpty());
            $test->assertEquals(false, $trip->getReturnWasLimitedBySpace());
            $test->assertEquals(false, $trip->getReturnWasLimitedByWeight());

            $test->assertEquals(300, $trip->getRoundTripDistance()->getValue());
            $test->assertEquals('miles', $trip->getRoundTripDistance()->getUnit());
            $test->assertEquals(['FR'], $trip->getCountriesTransitted());
            $test->assertEquals('Belgium', $trip->getCountriesTransittedOther());

            $isTrailerSwapped = $trailerConfiguration !== null || $axleConfiguration !== null;
            $test->assertEquals($isTrailerSwapped, $trip->getIsSwappedTrailer());
            $test->assertEquals($trailerConfiguration, $trip->getTrailerConfiguration());
            $test->assertEquals($axleConfiguration, $trip->getAxleConfiguration());
            $test->assertEquals($bodyType !== null, $trip->getIsChangedBodyType());
            $test->assertEquals($bodyType, $trip->getBodyType());

            $this->assertEquals($grossWeight, $trip->getGrossWeight());
            $this->assertEquals($carryingCapacity, $trip->getCarryingCapacity());
        });
    }

    public function originDestinationDatesAndPorts(): void
    {
        $this->formTestAction('#^/international-survey/vehicles/[^/]+/add-trip/origin-and-destination$#', 'origin_and_destination_continue', [
            new FormTestCase([
                'origin_and_destination[origin]' => 'Southampton',
                'origin_and_destination[destination]' => 'Paris',
            ]),
        ], [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);

        $this->formTestAction('#^/international-survey/vehicles/[^/]+/add-trip/dates$#', 'dates_continue', [
            new FormTestCase([
                'dates[outboundDate][day]' => '5',
                'dates[outboundDate][month]' => '5',
                'dates[outboundDate][year]' => '2021',
                'dates[returnDate][day]' => '8',
                'dates[returnDate][month]' => '5',
                'dates[returnDate][year]' => '2021',
            ]),
        ], [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);

        $this->formTestAction('#^/international-survey/vehicles/[^/]+/add-trip/outbound-ports$#', 'outbound_ports_and_cargo_state_continue', [
            new FormTestCase([
                'outbound_ports_and_cargo_state[ports]' => '1', // Portfoot - Waspwerp
                'outbound_ports_and_cargo_state[wasAtCapacity]' => '1',
                'outbound_ports_and_cargo_state[wasLimitedBy][]' => 'space',
            ]),
        ], [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);

        $this->formTestAction('#^/international-survey/vehicles/[^/]+/add-trip/return-ports$#', 'return_ports_and_cargo_state_continue', [
            new FormTestCase([
                'return_ports_and_cargo_state[ports]' => '1',
                'return_ports_and_cargo_state[wasAtCapacity]' => '0',
                'return_ports_and_cargo_state[wasEmpty]' => '1',
            ]),
        ], [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);
    }

    protected function keptSameTrailer(): void
    {
        $this->formTestAction('#^/international-survey/vehicles/[^/]+/add-trip/swapped-trailer$#', 'swapped_trailer_continue', [
            new FormTestCase([
                'swapped_trailer[isSwappedTrailer]' => '0',
            ]),
        ], [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);
    }

    protected function swappedTrailer(string $newAxleConfiguration): void
    {
        $this->formTestAction('#^/international-survey/vehicles/[^/]+/add-trip/swapped-trailer$#', 'swapped_trailer_continue', [
            new FormTestCase([
                'swapped_trailer[isSwappedTrailer]' => '1',
                'swapped_trailer[axleConfiguration]' => $newAxleConfiguration,
            ]),
        ], [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);
    }

    protected function keptSameBodyType(): void
    {
        $this->formTestAction('#^/international-survey/vehicles/[^/]+/add-trip/body-type$#', 'changed_body_type_continue', [
            new FormTestCase([
                'changed_body_type[isChangedBodyType]' => '0',
            ]),
        ], [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);
    }

    protected function changedBodyType(string $newBodyType): void
    {
        $this->formTestAction('#^/international-survey/vehicles/[^/]+/add-trip/body-type$#', 'changed_body_type_continue', [
            new FormTestCase([
                'changed_body_type[isChangedBodyType]' => '1',
                'changed_body_type[bodyType]' => $newBodyType,
            ]),
        ], [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);
    }

    public function weight(): void
    {
        $this->formTestAction('#^/international-survey/vehicles/[^/]+/add-trip/vehicle-weights$#', 'vehicle_weight_continue', [
            new FormTestCase([
                'vehicle_weight[carryingCapacity]' => '25000',
                'vehicle_weight[grossWeight]' => '32000',
            ]),
        ], [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);
    }

    protected function distanceAndCountries(): void
    {
        $this->formTestAction('#^/international-survey/vehicles/[^/]+/add-trip/distance$#', 'distance_continue', [
            new FormTestCase([
                'distance[roundTripDistance][unit]' => 'miles',
                'distance[roundTripDistance][value]' => '300',
            ]),
        ], [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);

        $this->formTestAction('#^/international-survey/vehicles/[^/]+/add-trip/countries-transitted$#', 'countries_transitted_continue', [
            new FormTestCase([
                'countries_transitted[countriesTransitted][]' => 'FR',
                'countries_transitted[countriesTransittedOther]' => 'Belgium',
            ]),
        ], [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);
    }
}
