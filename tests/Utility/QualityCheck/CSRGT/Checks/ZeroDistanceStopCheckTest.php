<?php

namespace App\Tests\Utility\QualityCheck\CSRGT\Checks;

use App\Tests\Utility\QualityCheck\CSRGT\AbstractCsrgtCheckTestCase;
use App\Utility\QualityCheck\CSRGT\Checks\ZeroDistanceStopCheck;
use App\Utility\QualityCheck\SubjectType;

class ZeroDistanceStopCheckTest extends AbstractCsrgtCheckTestCase
{
    public function testFiresOnZeroDistanceStop(): void
    {
        $day = $this->makeDay(1, [
            $this->makeStop('A', 'B', 0, 0.0, id: 'stop-x'),
        ]);

        $flags = (new ZeroDistanceStopCheck())->run($this->responseWithDays([$day]));

        $this->assertCount(1, $flags);
        $this->assertSame('zero-distance-stop', $flags[0]->reasonCode);
        $this->assertSame(SubjectType::DAY, $flags[0]->subjectType);
        $this->assertSame('day-1', $flags[0]->subjectId);
        $this->assertSame(['count' => 1, 'numbers' => '1'], $flags[0]->payload);
    }

    public function testFiresWhenDistanceNotRecorded(): void
    {
        // No distance set at all (Distance embeddable absent / null value)
        $day = $this->makeDay(1, [
            $this->makeStop('A', 'B', 0),
        ]);

        $this->assertCount(1, (new ZeroDistanceStopCheck())->run($this->responseWithDays([$day])));
    }

    public function testDoesNotFireWhenDistanceIsPositive(): void
    {
        $day = $this->makeDay(1, [
            $this->makeStop('A', 'B', 0, 0.5),
        ]);

        $this->assertSame([], (new ZeroDistanceStopCheck())->run($this->responseWithDays([$day])));
    }

    public function testIndependentOfOriginDestinationAndWeight(): void
    {
        // O=D and W>0 — still fires because distance is zero
        $day = $this->makeDay(1, [
            $this->makeStop('A', 'A', 1000, 0.0),
        ]);

        $this->assertCount(1, (new ZeroDistanceStopCheck())->run($this->responseWithDays([$day])));
    }

    public function testSkipsSummaryDays(): void
    {
        $summary = $this->makeSummary('A', 'B', 0, 0);
        $day = $this->makeSummaryDay(1, $summary);

        $this->assertSame([], (new ZeroDistanceStopCheck())->run($this->responseWithDays([$day])));
    }

    public function testGroupsMatchingStopsIntoOneFlag(): void
    {
        $day = $this->makeDay(1, [
            $this->makeStop('A', 'B', 0, 0.0, id: 'zero-1'),   // stop 1
            $this->makeStop('B', 'C', 500, 10.0, id: 'real-1'), // stop 2
            $this->makeStop('C', 'D', 0, 0.0, id: 'zero-2'),   // stop 3
        ]);

        $flags = (new ZeroDistanceStopCheck())->run($this->responseWithDays([$day]));

        // Three stops, two with zero distance → a single flag covering stops 1 and 3.
        $this->assertCount(1, $flags);
        $this->assertSame(['count' => 2, 'numbers' => '1,3'], $flags[0]->payload);
    }
}
