<?php

namespace App\Tests\Utility\QualityCheck\CSRGT\Checks;

use App\Tests\Utility\QualityCheck\CSRGT\AbstractCsrgtCheckTestCase;
use App\Utility\QualityCheck\CSRGT\Checks\SameOriginDestinationStopCheck;
use App\Utility\QualityCheck\SubjectType;

class SameOriginDestinationStopCheckTest extends AbstractCsrgtCheckTestCase
{
    public function testFiresWhenOriginEqualsDestination(): void
    {
        $day = $this->makeDay(1, [
            $this->makeStop('LL14 3AX', 'LL14 3AX', 0, 171.0, id: 'stop-x'),
        ]);

        $flags = (new SameOriginDestinationStopCheck())->run($this->responseWithDays([$day]));

        $this->assertCount(1, $flags);
        $this->assertSame('same-origin-destination-stop', $flags[0]->reasonCode);
        $this->assertSame(SubjectType::DAY, $flags[0]->subjectType);
        $this->assertSame('day-1', $flags[0]->subjectId);
        $this->assertSame(['count' => 1, 'numbers' => '1'], $flags[0]->payload);
    }

    public function testDoesNotFireWhenOriginAndDestinationDiffer(): void
    {
        $day = $this->makeDay(1, [
            $this->makeStop('A', 'B', 0, 50.0),
        ]);

        $this->assertSame([], (new SameOriginDestinationStopCheck())->run($this->responseWithDays([$day])));
    }

    public function testIndependentOfDistanceAndWeight(): void
    {
        // Same orig/dest with positive distance and positive weight — still fires
        $day = $this->makeDay(1, [
            $this->makeStop('A', 'A', 1000, 50.0),
        ]);

        $this->assertCount(1, (new SameOriginDestinationStopCheck())->run($this->responseWithDays([$day])));
    }

    public function testSkipsSummaryDays(): void
    {
        // Summary days legitimately have origin = destination (round-trip across many deliveries).
        $summary = $this->makeSummary('A', 'A', 5000, 5000, distanceLoadedMiles: 50.0);
        $day = $this->makeSummaryDay(1, $summary);

        $this->assertSame([], (new SameOriginDestinationStopCheck())->run($this->responseWithDays([$day])));
    }

    public function testGroupsMatchingStopsIntoOneFlag(): void
    {
        $day = $this->makeDay(1, [
            $this->makeStop('A', 'A', 0, 5.0, id: 'match-1'),   // stop 1
            $this->makeStop('B', 'C', 500, 10.0, id: 'no-match'), // stop 2
            $this->makeStop('X', 'X', 0, 0.0, id: 'match-2'),   // stop 3
        ]);

        $flags = (new SameOriginDestinationStopCheck())->run($this->responseWithDays([$day]));

        // Three stops, two matching → a single flag covering stops 1 and 3, not two flags.
        $this->assertCount(1, $flags);
        $this->assertSame(['count' => 2, 'numbers' => '1,3'], $flags[0]->payload);
    }
}
