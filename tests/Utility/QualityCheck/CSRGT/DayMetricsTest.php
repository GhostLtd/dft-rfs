<?php

namespace App\Tests\Utility\QualityCheck\CSRGT;

use App\Entity\Embeddable\Distance;
use App\Utility\QualityCheck\CSRGT\DayMetrics;

class DayMetricsTest extends AbstractCsrgtCheckTestCase
{
    public function testTotalWeightSumsAcrossStops(): void
    {
        $day = $this->makeDay(1, [
            $this->makeStop('A', 'B', 1000, 10.0, id: 's1'),
            $this->makeStop('B', 'C', 500, 10.0, id: 's2'),
            $this->makeStop('C', 'D', null, 10.0, id: 's3'), // null treated as 0
        ]);

        $this->assertSame(1500, DayMetrics::totalWeight($day));
    }

    public function testTotalWeightForSummaryDay(): void
    {
        $summary = $this->makeSummary('A', 'B', weightLoaded: 3000, weightUnloaded: 2500, distanceLoadedMiles: 50.0);
        $day = $this->makeSummaryDay(2, $summary);

        $this->assertSame(5500, DayMetrics::totalWeight($day));
    }

    public function testTotalWeightForSummaryDayWithoutAttachedSummary(): void
    {
        // Detailed day shape — no summary expected, should return 0 across no stops
        $day = $this->makeDay(1, []);
        $this->assertSame(0, DayMetrics::totalWeight($day));
    }

    public function testTotalDistanceSumsAcrossStops(): void
    {
        $day = $this->makeDay(1, [
            $this->makeStop('A', 'B', 0, 10.5, id: 's1'),
            $this->makeStop('B', 'C', 0, 0.5, id: 's2'),
        ]);

        $this->assertSame(11.0, DayMetrics::totalDistanceMiles($day));
    }

    public function testTotalDistanceForSummaryDayAddsLoadedAndUnloaded(): void
    {
        $summary = $this->makeSummary('A', 'B', 0, 0, distanceLoadedMiles: 30.0, distanceUnloadedMiles: 20.0);
        $day = $this->makeSummaryDay(2, $summary);

        $this->assertSame(50.0, DayMetrics::totalDistanceMiles($day));
    }

    public function testTotalDistanceConvertsKilometresToMiles(): void
    {
        $day = $this->makeDay(1, []);
        $km = new Distance();
        $km->setValue('161.0');
        $km->setUnit(Distance::UNIT_KILOMETRES);
        $stop = $this->makeStop('A', 'B', 0);
        $stop->setDistanceTravelled($km);
        $day->addStop($stop);

        // 161 km ≈ 100.04 miles
        $this->assertEqualsWithDelta(100.04, DayMetrics::totalDistanceMiles($day), 0.05);
    }

    public function testTotalDistanceHandlesMissingDistance(): void
    {
        $day = $this->makeDay(1, [
            $this->makeStop('A', 'B', 0), // distance not set
        ]);

        $this->assertSame(0.0, DayMetrics::totalDistanceMiles($day));
    }
}
