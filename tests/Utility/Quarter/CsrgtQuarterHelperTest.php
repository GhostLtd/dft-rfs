<?php

namespace App\Tests\Utility\Quarter;

use App\Utility\Quarter\CsrgtQuarterHelper;
use PHPUnit\Framework\TestCase;

class CsrgtQuarterHelperTest extends TestCase
{
    public function dataGetStartOfQuarterOffset(): array
    {
        return [
            [new \DateTime('2025-11-01'), 0, new \DateTime('2025-09-29')],

            [new \DateTime('2025-11-01'), -1, new \DateTime('2025-06-30')],
            [new \DateTime('2025-09-21'), -1, new \DateTime('2025-03-31')],
            [new \DateTime('2025-05-10'), -1, new \DateTime('2024-12-30')],
            [new \DateTime('2025-01-29'), -1, new \DateTime('2024-09-30')],
      ];
    }

    /**
     * @dataProvider dataGetStartOfQuarterOffset
     */
    public function testGetStartOfQuarterOffset(\DateTime $currentDate, int $deltaQuarter, \DateTime $expectedDate): void
    {
        $helper =  new CsrgtQuarterHelper();
        $actualDate = $helper->getStartOfQuarterOffset($deltaQuarter, $currentDate);

        $this->assertEquals($expectedDate, $actualDate);
    }


    public function dataGetDateRangeForYearAndQuarter(): array
    {
        return [
            [2022, 1, ['2021/12/27', '2022/03/28']],
            [2024, 1, ['2024/01/01', '2024/04/01']],

            [2020, 4, ['2020/09/28', '2020/12/28']],
            [2021, 1, ['2020/12/28', '2021/03/29']],

            [2023, 4, ['2023/09/25', '2024/01/01']],
        ];
    }

    /**
     * @dataProvider dataGetDateRangeForYearAndQuarter
     */
    public function testGetDateRangeForYearAndQuarter(int $year, int $quarter, array $expectedDateRange)
    {
        $helper =  new CsrgtQuarterHelper();

        $expectedDateRange = array_map(fn($v) => new \DateTime($v), $expectedDateRange);
        $dateRange = $helper->getDateRangeForYearAndQuarter($year, $quarter);
        $this->assertEquals($expectedDateRange, $dateRange);
    }

    public function dataGetQuarterAndYear(): array
    {
        return [
            ['2021/12/27', 1, 2022],
            ['2022/03/27', 1, 2022],

            ['2024/01/01', 1, 2024],
            ['2024/03/31', 1, 2024],

            ['2020/09/28', 4, 2020],
            ['2020/12/27', 4, 2020],

            ['2020/12/28', 1, 2021],
            ['2021/03/28', 1, 2021],

            ['2023/09/25', 4, 2023],
            ['2023/12/31', 4, 2023],
        ];
    }

    /**
     * @dataProvider dataGetQuarterAndYear
     */
    public function testGetQuarterAndYear(string $date, int $expectedQuarter, int $expectedYear)
    {
        $helper =  new CsrgtQuarterHelper();
        $quarterAndYear = $helper->getQuarterAndYear(new \DateTime($date));
        $this->assertEquals([$expectedQuarter, $expectedYear], $quarterAndYear);
    }
}
