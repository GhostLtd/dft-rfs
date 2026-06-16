<?php

namespace App\Tests\Utility\International;

use App\Utility\International\WeekNumberHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class WeekNumberHelperTest extends TestCase
{
    public static function providerWeekNumber()
    {
        return [
            ['2021/07/24', 1556],
            ['2021/07/25', 1557],
            ['2021/07/31', 1557],
            ['2021/08/01', 1558],
        ];
    }

    #[DataProvider('providerWeekNumber')]
    public function testWeekNumber(string $date, int $expectedWeekNumber)
    {
        $weekNumber = WeekNumberHelper::getWeekNumber(new \DateTime($date));

        $this->assertEquals($expectedWeekNumber, $weekNumber);
    }

    public static function providerDateFromWeekAndYear()
    {
        return [
            [1557, '2021/07/25'],
            [1558, '2021/08/01'],
        ];
    }

    #[DataProvider('providerDateFromWeekAndYear')]
    public function testDateFromWeekAndYear(int $weekNumber, ?string $expectedDate)
    {
        $date = WeekNumberHelper::getDateForWeekNumber($weekNumber);

        if ($expectedDate === null) {
            $this->assertEquals($expectedDate, $date);
        } else {
            $this->assertEquals(new \DateTime($expectedDate), $date);
        }
    }
}