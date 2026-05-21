<?php

namespace App\Tests\Utility\QualityCheck;

use App\Entity\Domestic\Day;
use App\Entity\Domestic\DayStop;
use App\Utility\QualityCheck\FlagSet;
use App\Utility\QualityCheck\QualityFlag;
use App\Utility\QualityCheck\SubjectType;
use PHPUnit\Framework\TestCase;

class FlagSetTest extends TestCase
{
    public function testEmptySetSemantics(): void
    {
        $set = new FlagSet();
        $this->assertTrue($set->isEmpty());
        $this->assertSame(0, $set->count());
        $this->assertSame([], $set->all());
    }

    public function testAddAndCount(): void
    {
        $set = new FlagSet();
        $set->add($this->makeFlag(SubjectType::DAY, 'd1'));
        $set->add($this->makeFlag(SubjectType::DAY, 'd2'));

        $this->assertFalse($set->isEmpty());
        $this->assertSame(2, $set->count());
    }

    public function testForSubjectFiltersByTypeAndId(): void
    {
        $set = new FlagSet([
            $this->makeFlag(SubjectType::DAY, 'd1', 'a'),
            $this->makeFlag(SubjectType::DAY, 'd2', 'b'),
            $this->makeFlag(SubjectType::STOP, 'd1', 'c'), // same id, different type
        ]);

        $filtered = $set->forSubject(SubjectType::DAY, 'd1');
        $this->assertSame(1, $filtered->count());
        $this->assertSame('a', $filtered->all()[0]->reasonCode);
    }

    public function testForResponseFiltersToResponseSubjectOnly(): void
    {
        $set = new FlagSet([
            $this->makeFlag(SubjectType::RESPONSE, 'r1', 'a'),
            $this->makeFlag(SubjectType::DAY, 'd1', 'b'),
            $this->makeFlag(SubjectType::STOP, 's1', 'c'),
            $this->makeFlag(SubjectType::RESPONSE, 'r2', 'd'),
        ]);

        $filtered = $set->forResponse();
        $this->assertSame(2, $filtered->count());
        $codes = array_map(fn($f) => $f->reasonCode, $filtered->all());
        $this->assertSame(['a', 'd'], $codes);
    }

    public function testForDayMatchesByEntityId(): void
    {
        $day = $this->createMock(Day::class);
        $day->method('getId')->willReturn('day-uuid');

        $set = new FlagSet([
            $this->makeFlag(SubjectType::DAY, 'day-uuid', 'matches'),
            $this->makeFlag(SubjectType::DAY, 'other', 'no-match'),
            $this->makeFlag(SubjectType::STOP, 'day-uuid', 'wrong-type'),
        ]);

        $filtered = $set->forDay($day);
        $this->assertSame(1, $filtered->count());
        $this->assertSame('matches', $filtered->all()[0]->reasonCode);
    }

    public function testForStopMatchesByEntityId(): void
    {
        $stop = $this->createMock(DayStop::class);
        $stop->method('getId')->willReturn('stop-uuid');

        $set = new FlagSet([
            $this->makeFlag(SubjectType::STOP, 'stop-uuid', 'matches'),
            $this->makeFlag(SubjectType::DAY, 'stop-uuid', 'wrong-type'),
        ]);

        $filtered = $set->forStop($stop);
        $this->assertSame(1, $filtered->count());
        $this->assertSame('matches', $filtered->all()[0]->reasonCode);
    }

    public function testIsIterable(): void
    {
        $set = new FlagSet([
            $this->makeFlag(SubjectType::DAY, 'd1', 'a'),
            $this->makeFlag(SubjectType::DAY, 'd2', 'b'),
        ]);

        $codes = [];
        foreach ($set as $flag) {
            $codes[] = $flag->reasonCode;
        }
        $this->assertSame(['a', 'b'], $codes);
    }

    public function testEmptySetHasEmptySignature(): void
    {
        $this->assertSame('', (new FlagSet())->signature());
    }

    public function testSignatureIsOrderIndependent(): void
    {
        $a = $this->makeFlag(SubjectType::STOP, 's1', 'zero-distance-stop');
        $b = $this->makeFlag(SubjectType::DAY, 'd1', 'whole-day-empty');

        $this->assertSame(
            (new FlagSet([$a, $b]))->signature(),
            (new FlagSet([$b, $a]))->signature(),
        );
    }

    public function testSignatureDiffersByReasonCode(): void
    {
        $one = new FlagSet([$this->makeFlag(SubjectType::STOP, 's1', 'zero-distance-stop')]);
        $two = new FlagSet([$this->makeFlag(SubjectType::STOP, 's1', 'same-origin-destination-stop')]);

        $this->assertNotSame($one->signature(), $two->signature());
    }

    public function testSignatureDiffersByPayload(): void
    {
        // Same day + reason, but a different set of stops — the signature must change so a reviewed
        // ("seen") survey is re-flagged when stop membership changes.
        $one = new FlagSet([QualityFlag::forStops('day-1', 'zero-distance-stop', [1, 2])]);
        $two = new FlagSet([QualityFlag::forStops('day-1', 'zero-distance-stop', [1, 2, 3])]);

        $this->assertNotSame($one->signature(), $two->signature());
    }

    public function testSignatureDiffersBySubject(): void
    {
        $one = new FlagSet([$this->makeFlag(SubjectType::STOP, 's1', 'zero-distance-stop')]);
        $two = new FlagSet([$this->makeFlag(SubjectType::STOP, 's2', 'zero-distance-stop')]);

        $this->assertNotSame($one->signature(), $two->signature());
    }

    private function makeFlag(SubjectType $type, string $id, string $code = 'test'): QualityFlag
    {
        return new QualityFlag(
            subjectType: $type,
            subjectId: $id,
            reasonCode: $code,
        );
    }
}
