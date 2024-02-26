<?php

namespace App\Tests\NewFunctional\Wizard\International;

use App\Tests\DataFixtures\International\TripFixtures;
use App\Tests\NewFunctional\Wizard\Action\Context;
use App\Tests\NewFunctional\Wizard\Action\PathTestAction;
use Symfony\Component\Intl\Countries;

abstract class AbstractTripTest extends AbstractSurveyTest
{
    protected function performChangeTest(int $linkIndex, \Closure $callback): void
    {
        $this->initialiseTest([TripFixtures::class]);
        $this->pathTestAction('/international-survey');
        $this->clickLinkContaining('View', 1);
        $this->pathTestAction('#^\/international-survey\/vehicles\/[a-f0-9-]+$#', [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);
        $this->clickLinkContaining('View');
        $this->pathTestAction('#^\/international-survey\/trips\/[a-f0-9-]+$#', [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true]);

        $data = $this->getInitialData();

        $this->assertDashboardMatchesData($data);
        $this->assertDatabaseMatchesData($data);

        $this->clickLinkContaining('Change', $linkIndex);

        $callback($data);

        $this->assertDashboardMatchesData($data);
        $this->assertDatabaseMatchesData($data);
    }

    protected function getInitialData(): array
    {
        return [
            'outboundDate' => new \DateTime('2021-05-01'),
            'returnDate' => new \DateTime('2021-05-03'),
            'isChangedBodyType' => false,
            'isSwappedTrailer' => false,
            'outboundUkPort' => 'Portfoot',
            'outboundForeignPort' => 'Waspwerp',
            'returnForeignPort' => 'Waspwerp',
            'returnUkPort' => 'Portfoot',
            'roundTripDistance' => [
                'unit' => 'miles',
                'value' => '1450',
            ],
            'countriesTransitted' => ['FR', 'BE', 'NL', 'DE'],
            'countriesTransittedOther' => null,
            'returnWasEmpty' => false,
            'returnWasLimitedBySpace' => false,
            'returnWasLimitedByWeight' => false,
            'outboundWasEmpty' => false,
            'outboundWasLimitedBySpace' => false,
            'outboundWasLimitedByWeight' => false,
            'origin' => 'Chichester',
            'destination' => 'Chichester',
        ];
    }

    protected function assertDashboardMatchesData(array $data, bool $isRigid=false): void
    {
        $stateToString = fn(bool $wasEmpty, ?bool $wasLimitedBySpace, ?bool $wasLimitedByWeight) =>
            match([$wasEmpty, $wasLimitedBySpace, $wasLimitedByWeight]) {
                [false, false, false] => ' (not at capacity)',
                [false, true, true] => ' (at capacity by space and weight)',
                [false, true, false] => ' (at capacity by space)',
                [false, false, true] => ' (at capacity by weight)',
                [true, null, null] => ' (empty)',
            };

        $outboundState = $stateToString($data['outboundWasEmpty'], $data['outboundWasLimitedBySpace'], $data['outboundWasLimitedByWeight']);
        $returnState = $stateToString($data['returnWasEmpty'], $data['returnWasLimitedBySpace'], $data['returnWasLimitedByWeight']);

        $countries = array_map(
            fn(string $c) => Countries::getName($c),
            $data['countriesTransitted']
        );

        if ($data['countriesTransittedOther'] ?? null) {
            $countries = array_merge(
                $countries,
                array_map(trim(...), explode(',', $data['countriesTransittedOther'])),
            );
        }

        sort($countries);

        $countries = join(', ', $countries);

        $outboundDate = $data['outboundDate']->format('d/m/Y');
        $returnDate = $data['returnDate']->format('d/m/Y');

        $distance = number_format($data['roundTripDistance']['value']);

        if ($data['isSwappedTrailer']) {
            $bodyType = match($data['axleConfiguration']) {
                232 => 'Rigid 3 axle + 2 axle trailer',
                332 => 'Articulated 3 axle + 2 axle trailer',
            };

            $trailer = "Yes\n\n{$bodyType}";
        } else {
            $trailer = 'No';
        }

        $expectedData = [
            'Start / end' => "{$data['origin']} to {$data['destination']}",
            'Dates' => "{$outboundDate} to {$returnDate}",
            'Outbound' => "{$data['outboundUkPort']} to {$data['outboundForeignPort']}{$outboundState}",
            'Return' => "{$data['returnForeignPort']} to {$data['returnUkPort']}{$returnState}",
            'Trailer' => $trailer,
            'Round trip' => "{$distance} {$data['roundTripDistance']['unit']}",
            'Countries travelled' => $countries,
        ];

        if (!$isRigid) {
            if ($data['isChangedBodyType']) {
                $bodyType = match($data['bodyType']) {
                    'box' => 'Box/non-specialised',
                    'liquid' => 'Liquid tanker',
                    'temperature-controlled' => 'Temperature controlled',
                };

                $body = "Yes — {$bodyType}";
            } else {
                $body = 'No';
            }

            $expectedData['Body'] = $body;
        }

        $this->assertSummaryListData($expectedData);
    }

    protected function assertDatabaseMatchesData(array $data): void
    {
        $this->callbackTestAction(function (Context $context) use ($data) {
            $test = $context->getTestCase();
            $trip = $this->getTrip($context->getEntityManager(), $test);

            $this->assertDataMatches($trip, $data, 'trip');
        });
    }
}
