<?php

namespace App\Tests\Utility\QualityCheck\CSRGT;

use App\Entity\Domestic\Day;
use App\Entity\Domestic\DayStop;
use App\Entity\Domestic\DaySummary;
use App\Entity\Domestic\SurveyResponse;
use App\Entity\Embeddable\Distance;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

/**
 * Shared fixture builders for CSRGT QualityCheck tests. Entities are
 * constructed in-memory; ids are set via reflection so checks that emit them
 * as QualityFlag::subjectId get a stable value.
 */
abstract class AbstractCsrgtCheckTestCase extends TestCase
{
    /** @param Day[] $days */
    protected function responseWithDays(array $days, string $id = 'r1'): SurveyResponse
    {
        $response = $this->createMock(SurveyResponse::class);
        $response->method('getDays')->willReturn(new ArrayCollection($days));
        $response->method('getId')->willReturn($id);
        return $response;
    }

    /** @param DayStop[] $stops */
    protected function makeDay(int $number, array $stops = [], ?string $id = null): Day
    {
        $day = new Day();
        $day->setNumber($number);
        $this->setId($day, Day::class, $id ?? "day-{$number}");
        foreach ($stops as $stop) {
            $day->addStop($stop);
        }
        return $day;
    }

    protected function makeSummaryDay(int $number, DaySummary $summary, ?string $id = null): Day
    {
        $day = new Day();
        $day->setNumber($number);
        $day->setHasMoreThanFiveStops(true);
        $day->setSummary($summary);
        $this->setId($day, Day::class, $id ?? "day-{$number}");
        return $day;
    }

    protected function makeStop(
        string $origin,
        string $destination,
        ?int $weight = null,
        ?float $distanceMiles = null,
        string $id = 'stop-1',
    ): DayStop {
        $stop = new DayStop();
        $stop->setOriginLocation($origin);
        $stop->setDestinationLocation($destination);
        $stop->setWeightOfGoodsCarried($weight);
        if ($distanceMiles !== null) {
            $stop->setDistanceTravelled($this->miles($distanceMiles));
        }
        $this->setId($stop, DayStop::class, $id);
        return $stop;
    }

    protected function makeSummary(
        string $origin,
        string $destination,
        int $weightLoaded = 0,
        int $weightUnloaded = 0,
        ?float $distanceLoadedMiles = null,
        ?float $distanceUnloadedMiles = null,
    ): DaySummary {
        $summary = new DaySummary();
        $summary->setOriginLocation($origin);
        $summary->setDestinationLocation($destination);
        $summary->setWeightOfGoodsLoaded($weightLoaded);
        $summary->setWeightOfGoodsUnloaded($weightUnloaded);
        if ($distanceLoadedMiles !== null) {
            $summary->setDistanceTravelledLoaded($this->miles($distanceLoadedMiles));
        }
        if ($distanceUnloadedMiles !== null) {
            $summary->setDistanceTravelledUnloaded($this->miles($distanceUnloadedMiles));
        }
        return $summary;
    }

    protected function miles(float $value): Distance
    {
        $d = new Distance();
        $d->setValue((string) $value);
        $d->setUnit(Distance::UNIT_MILES);
        return $d;
    }

    protected function setId(object $entity, string $class, string $id): void
    {
        $property = (new \ReflectionClass($class))->getProperty('id');
        $property->setValue($entity, $id);
    }
}
