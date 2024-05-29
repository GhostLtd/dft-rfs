<?php

namespace App\Tests\Utility\Quarter;

use App\Utility\Quarter\NaturalQuarterHelper;
use PHPUnit\Framework\TestCase;

class QuarterHelperTest extends TestCase
{
    public function dataAddQuartersToQuarterAndYear(): array
    {
        return [
            [2023, 4, 0, 2023, 4],
            [2023, 1, 1, 2023, 2],
            [2024, 4, 1, 2025, 1],
            [2023, 2, 4, 2024, 2],
            [2023, 2, 13, 2026, 3],
            [2023, 2, -8, 2021, 2],
            [2023, 2, -5, 2022, 1],
        ];
    }

    /**
     * @dataProvider dataAddQuartersToQuarterAndYear
     */
    public function testAddQuartersToQuarterAndYear(int $year, int $quarter, int $delta, int $expectedYear, int $expectedQuarter): void
    {
        $helper =  new NaturalQuarterHelper();
        [$resultYear, $resultQuarter] = $helper->addQuartersToQuarterAndYear($year, $quarter, $delta);

        $this->assertEquals($expectedYear, $resultYear);
        $this->assertEquals($expectedQuarter, $resultQuarter);
    }

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
        $helper =  new NaturalQuarterHelper();
        [$quarter, $year] = $helper->getQuarterAndYear(new \DateTime($date));

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
        $helper =  new NaturalQuarterHelper();
        [$lowerBound, $upperBound] = $helper->getDateRangeForYearAndQuarter($year, $quarter);

        $this->assertEquals($expectedLowerBound, $lowerBound->format('Y-m-d'));
        $this->assertEquals($expectedUpperBound, $upperBound->format('Y-m-d'));
    }
}
