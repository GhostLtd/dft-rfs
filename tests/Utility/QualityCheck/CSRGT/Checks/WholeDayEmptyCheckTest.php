<?php

namespace App\Tests\Utility\QualityCheck\CSRGT\Checks;

use App\Tests\Utility\QualityCheck\CSRGT\AbstractCsrgtCheckTestCase;
use App\Utility\QualityCheck\CSRGT\Checks\WholeDayEmptyCheck;
use App\Utility\QualityCheck\SubjectType;

class WholeDayEmptyCheckTest extends AbstractCsrgtCheckTestCase
{
    public function testFiresOnDetailedDayAllEmptyWithDistance(): void
    {
        // DF19 Sat shape: single empty stop with distance
        $day = $this->makeDay(6, [
            $this->makeStop('A', 'B', 0, 63.0),
        ]);

        $flags = (new WholeDayEmptyCheck())->run($this->responseWithDays([$day]));

        $this->assertCount(1, $flags);
        $this->assertSame('whole-day-empty', $flags[0]->reasonCode);
        $this->assertSame(SubjectType::DAY, $flags[0]->subjectType);
        $this->assertSame('day-6', $flags[0]->subjectId);
    }

    public function testFiresOnMultiStopDetailedDayAllEmptyWithDistance(): void
    {
        // FJ25 Sun shape: two empty stops over 102 miles
        $day = $this->makeDay(7, [
            $this->makeStop('A', 'B', 0, 1.0, id: 's1'),
            $this->makeStop('B', 'C', 0, 101.0, id: 's2'),
        ]);

        $this->assertCount(1, (new WholeDayEmptyCheck())->run($this->responseWithDays([$day])));
    }

    public function testFiresOnSummaryDayZeroWeights(): void
    {
        $summary = $this->makeSummary('A', 'B', 0, 0, distanceLoadedMiles: 0.0, distanceUnloadedMiles: 50.0);
        $day = $this->makeSummaryDay(2, $summary);

        $this->assertCount(1, (new WholeDayEmptyCheck())->run($this->responseWithDays([$day])));
    }

    public function testDoesNotFireWhenAnyWeightCarried(): void
    {
        $day = $this->makeDay(1, [
            $this->makeStop('A', 'B', 1000, 10.0),
        ]);

        $this->assertSame([], (new WholeDayEmptyCheck())->run($this->responseWithDays([$day])));
    }

    public function testDoesNotFireWhenZeroDistance(): void
    {
        // PR5 case — already surfaced by ZeroDistanceStopCheck, must not also be a whole-day-empty
        $day = $this->makeDay(1, [
            $this->makeStop('A', 'A', 0, 0.0),
        ]);

        $this->assertSame([], (new WholeDayEmptyCheck())->run($this->responseWithDays([$day])));
    }

    public function testSummaryDayWithLoadDoesNotFire(): void
    {
        $summary = $this->makeSummary('A', 'B', 5000, 5000, distanceLoadedMiles: 50.0);
        $day = $this->makeSummaryDay(2, $summary);

        $this->assertSame([], (new WholeDayEmptyCheck())->run($this->responseWithDays([$day])));
    }

    public function testFiresOncePerEmptyDayAcrossMultipleDays(): void
    {
        $emptyDay = $this->makeDay(1, [$this->makeStop('A', 'B', 0, 30.0)]);
        $loadedDay = $this->makeDay(2, [$this->makeStop('B', 'C', 1000, 50.0)]);
        $alsoEmpty = $this->makeDay(3, [$this->makeStop('C', 'D', 0, 10.0)]);

        $flags = (new WholeDayEmptyCheck())->run($this->responseWithDays([$emptyDay, $loadedDay, $alsoEmpty]));
        $this->assertCount(2, $flags);
        $this->assertSame(['day-1', 'day-3'], array_map(fn($f) => $f->subjectId, $flags));
    }
}
