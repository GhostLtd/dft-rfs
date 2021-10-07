<?php

namespace App\Tests\Utility;

use App\Utility\International\ReportQuarterHelper;
use PHPUnit\Framework\TestCase;

class ReportQuarterHelperTest extends TestCase
{
    public function dateToQuarterAndYearConversion(): array
    {
        return [
            ['2020/12/31', 4, 2020],
            ['2021/1/1', 1, 2021],
            ['2021/3/31', 1, 2021],
            ['2021/4/1', 2, 2021],
            ['2021/6/30', 2, 2021],
            ['2021/7/1', 3, 2021],
            ['2021/9/30', 3, 2021],
            ['2021/10/1', 4, 2021],
            ['2021/12/31', 4, 2021],
            ['2022/1/1', 1, 2022],
        ];
    }

    /**
     * @dataProvider dateToQuarterAndYearConversion
     */
    public function testDateToQuarterAndYearConversion(string $date, int $expectedQuarter, int $expectedYear)
    {
        [$quarter, $year] = ReportQuarterHelper::getQuarterAndYear(new \DateTime($date));

        $this->assertEquals($expectedQuarter, $quarter);
        $this->assertEquals($expectedYear, $year);
    }

    public function quarterAndYearToDateRangeConversion(): array
    {
        return [
            [1, 2021, '2021-01-01', '2021-04-01'],
            [2, 2021, '2021-04-01', '2021-07-01'],
            [3, 2021, '2021-07-01', '2021-10-01'],
            [4, 2021, '2021-10-01', '2022-01-01'],
        ];
    }

    /**
     * @dataProvider quarterAndYearToDateRangeConversion
     */
    public function testQuarterAndYearToDateRangeConversion(int $quarter, int $year, string $expectedLowerBound, string $expectedUpperBound)
    {
        [$lowerBound, $upperBound] = ReportQuarterHelper::getDateRangeForYearAndQuarter($year, $quarter);

        $this->assertEquals($expectedLowerBound, $lowerBound->format('Y-m-d'));
        $this->assertEquals($expectedUpperBound, $upperBound->format('Y-m-d'));
    }
}