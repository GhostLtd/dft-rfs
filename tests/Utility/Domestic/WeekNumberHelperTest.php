<?php

namespace App\Tests\Utility\Domestic;

use App\Utility\Domestic\WeekNumberHelper;
use PHPUnit\Framework\TestCase;

class WeekNumberHelperTest extends TestCase
{
    public function providerWeekNumber()
    {
        return [
            ['2021/12/27', 1, 2022],
            ['2022/01/02', 1, 2022],
            ['2022/01/03', 2, 2022],
            ['2024/01/01', 1, 2024],
            ['2024/01/07', 1, 2024],
            ['2024/01/08', 2, 2024],

            ['2020/09/28', 40, 2020],
            ['2020/10/05', 41, 2020],
            ['2020/12/21', 52, 2020],
            ['2020/12/27', 52, 2020],
            ['2020/12/28', 1, 2021],
        ];
    }

    /**
     * @dataProvider providerWeekNumber
     */
    public function testWeekNumber(string $date, int $expectedWeekNumber, int $expectedYear)
    {
        [$weekNumber, $year] = WeekNumberHelper::getYearlyWeekNumberAndYear(new \DateTime($date));

        $this->assertEquals($expectedWeekNumber, $weekNumber);
        $this->assertEquals($expectedYear, $year);
    }

    public function providerDateFromWeekAndYear()
    {
        return [
            [2022, 1, '2021/12/27'],
            [2022, 2, '2022/01/03'],
            [2024, 1, '2024/01/01'],
            [2024, 2, '2024/01/08'],

            [2020, 40, '2020/09/28'],
            [2020, 41, '2020/10/05'],
            [2020, 52, '2020/12/21'],
            [2021, 1, '2020/12/28'],

            [2020, 53, null],
            [2020, 0, null],

            [2021, 53, null],
            [2022, 53, null],
            [2024, 53, null],

            // The first week of this year only includes 1st Jan.
            // The second week starts 2nd Jan.
            // This causes there to be 53 weeks in the year.
            [2023, 53, '2023/12/25'],
        ];
    }

    /**
     * @dataProvider providerDateFromWeekAndYear
     */
    public function testDateFromWeekAndYear(int $year, int $weekNumber, ?string $expectedDate)
    {
        $date = WeekNumberHelper::getDateForYearAndWeek($year, $weekNumber);

        if ($expectedDate === null) {
            $this->assertEquals($expectedDate, $date);
        } else {
            $this->assertEquals(new \DateTime($expectedDate), $date);
        }
    }

    public function providerYearForWeekNumberAndGenDate()
    {
        return [
            [1, '202011191435', 2021],
            [10, '202011191435', 2021],
            [47, '202011191435', 2020],
            [48, '202011191435', 2020],
        ];
    }

    /**
     * @dataProvider providerYearForWeekNumberAndGenDate
     */
    public function testYearForWeekNumberAndGenDate(int $weekNumber, string $genDate, int $expectedYear)
    {
        $genYear = WeekNumberHelper::getYearForWeekNumberAndGenDate($weekNumber, new \DateTime($genDate));
        $this->assertEquals($expectedYear, $genYear);
    }
}
