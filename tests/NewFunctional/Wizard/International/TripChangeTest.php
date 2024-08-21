<?php

namespace App\Tests\NewFunctional\Wizard\International;

use App\Tests\NewFunctional\Wizard\Action\PathTestAction;
use App\Tests\NewFunctional\Wizard\Form\FormTestCase;

class TripChangeTest extends AbstractTripTest
{
    public function testChangeOriginAndDestination(): void
    {
        $this->performChangeTest(0, function(array &$expectedData) {
            $options = [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true];

            $this->formTestAction('#^\/international-survey\/trips\/[a-f0-9-]+\/edit\/origin-and-destination$#', 'origin_and_destination_continue', [
                new FormTestCase([
                    'origin_and_destination[origin]' => 'Felpham',
                    'origin_and_destination[destination]' => 'Gosport',
                ]),
            ], $options);

            $expectedData['origin'] = 'Felpham';
            $expectedData['destination'] = 'Gosport';
        });
    }

    public function testChangeDates(): void
    {
        $this->performChangeTest(1, function(array &$expectedData) {
            $options = [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true];

            $this->formTestAction('#^\/international-survey\/trips\/[a-f0-9-]+\/edit\/dates$#', 'dates_continue', [
                new FormTestCase([
                    'dates[outboundDate][day]' => '2',
                    'dates[outboundDate][month]' => '5',
                    'dates[outboundDate][year]' => '2021',
                    'dates[returnDate][day]' => '8',
                    'dates[returnDate][month]' => '5',
                    'dates[returnDate][year]' => '2021',                ]),
            ], $options);

            $expectedData['outboundDate'] = new \DateTime('2021-05-02');
            $expectedData['returnDate'] = new \DateTime('2021-05-08');
        });
    }

    public function testChangeOutboundPorts(): void
    {
        $this->performChangeTest(2, function(array &$expectedData) {
            $options = [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true];

            $this->formTestAction('#^\/international-survey\/trips\/[a-f0-9-]+\/edit\/outbound-ports$#', 'outbound_ports_and_cargo_state_continue', [
                new FormTestCase([
                    'outbound_ports_and_cargo_state[ports]' => '3',
                    'outbound_ports_and_cargo_state[wasAtCapacity]' => 'yes',
                    'outbound_ports_and_cargo_state[wasLimitedBy]' => ['space', 'weight'],
                ]),
            ], $options);

            $expectedData['outboundUkPort'] = 'Southester';
            $expectedData['outboundForeignPort'] = 'Roysterdam';
            $expectedData['outboundWasLimitedBySpace'] = true;
            $expectedData['outboundWasLimitedByWeight'] = true;
        });
    }

    public function testChangeReturnPorts(): void
    {
        $this->performChangeTest(3, function(array &$expectedData) {
            $options = [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true];

            $this->formTestAction('#^\/international-survey\/trips\/[a-f0-9-]+\/edit\/return-ports$#', 'return_ports_and_cargo_state_continue', [
                new FormTestCase([
                    'return_ports_and_cargo_state[ports]' => '1',
                    'return_ports_and_cargo_state[wasAtCapacity]' => 'no',
                    'return_ports_and_cargo_state[wasEmpty]' => 'yes',
                ]),
            ], $options);

            $expectedData['returnUkPort'] = 'Portfoot';
            $expectedData['returnForeignPort'] = 'Roysterdam';
            $expectedData['returnWasLimitedBySpace'] = null;
            $expectedData['returnWasLimitedByWeight'] = null;
            $expectedData['returnWasEmpty'] = true;
        });
    }

    public function testChangeAxleConfig(): void
    {
        $this->performChangeTest(4, function(array &$expectedData) {
            $options = [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true];

            $this->formTestAction('#^\/international-survey\/trips\/[a-f0-9-]+\/edit\/swapped-trailer$#', 'swapped_trailer_continue', [
                new FormTestCase([
                    'swapped_trailer[isSwappedTrailer]' => 'yes',
                    'swapped_trailer[axleConfiguration]' => 332,
                ]),
            ], $options);

            $this->formTestAction('#^\/international-survey\/trips\/[a-f0-9-]+\/edit\/body-type$#', 'changed_body_type_continue', [
                new FormTestCase([
                    'changed_body_type[isChangedBodyType]' => 'yes',
                    'changed_body_type[bodyType]' => 'temperature-controlled',
                ]),
            ], $options);

            $this->formTestAction('#^\/international-survey\/trips\/[a-f0-9-]+\/edit\/vehicle-weights$#', 'vehicle_weight_continue', [
                new FormTestCase([
                    'vehicle_weight[grossWeight]' => 25000,
                    'vehicle_weight[carryingCapacity]' => 22000,
                ]),
            ], $options);

            $expectedData['isSwappedTrailer'] = 'yes';
            $expectedData['axleConfiguration'] = 332;
            $expectedData['isChangedBodyType'] = 'yes';
            $expectedData['bodyType'] = 'temperature-controlled';
            $expectedData['grossWeight'] = 25000;
            $expectedData['carryingCapacity'] = 22000;
        });
    }

    public function testChangeDistance(): void
    {
        $this->performChangeTest(6, function(array &$expectedData) {
            $options = [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true];

            $this->formTestAction('#^\/international-survey\/trips\/[a-f0-9-]+\/edit\/distance$#', 'distance_continue', [
                new FormTestCase([
                    'distance[roundTripDistance][value]' => '333',
                    'distance[roundTripDistance][unit]' => 'kilometres',
                ]),
            ], $options);

            $expectedData['roundTripDistance']['value'] = '333';
            $expectedData['roundTripDistance']['unit'] = 'kilometres';
        });
    }

    public function testChangeCountries(): void
    {
        $this->performChangeTest(7, function(array &$expectedData) {
            $options = [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true];

            $this->formTestAction('#^\/international-survey\/trips\/[a-f0-9-]+\/edit\/countries-transitted#', 'countries_transitted_continue', [
                new FormTestCase([
                    'countries_transitted[countriesTransitted]' => ['FR', 'DE'],
                    'countries_transitted[countriesTransittedOther]' => 'Sweden, Denmark',
                ]),
            ], $options);

            $expectedData['countriesTransitted'] = ['FR', 'DE'];
            $expectedData['countriesTransittedOther'] = 'Sweden, Denmark';
        });
    }
}
