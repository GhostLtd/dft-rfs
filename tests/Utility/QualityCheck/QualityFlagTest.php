<?php

namespace App\Tests\Utility\QualityCheck;

use App\Utility\QualityCheck\QualityFlag;
use App\Utility\QualityCheck\SubjectType;
use PHPUnit\Framework\TestCase;

class QualityFlagTest extends TestCase
{
    public function testForStopsAggregatesToDayLevelWithSortedNumbers(): void
    {
        $flag = QualityFlag::forStops('day-7', 'zero-distance-stop', [3, 1, 2]);

        $this->assertSame(SubjectType::DAY, $flag->subjectType);
        $this->assertSame('day-7', $flag->subjectId);
        $this->assertSame('zero-distance-stop', $flag->reasonCode);
        $this->assertSame(['count' => 3, 'numbers' => '1,2,3'], $flag->payload);
    }

    public function testForStopsWithSingleStop(): void
    {
        $flag = QualityFlag::forStops('day-1', 'zero-distance-stop', [4]);

        $this->assertSame(['count' => 1, 'numbers' => '4'], $flag->payload);
    }
}
