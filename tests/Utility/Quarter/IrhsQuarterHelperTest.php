<?php

namespace App\Tests\Utility\Quarter;

use App\Utility\Quarter\IrhsQuarterHelper;
use PHPUnit\Framework\TestCase;

class IrhsQuarterHelperTest extends TestCase
{
    public function dataGetStartOfQuarterOffset(): array
    {
        return [
            [new \DateTime('2025-11-01'), 0, new \DateTime('2025-09-28')],

            [new \DateTime('2025-11-01'), -1, new \DateTime('2025-06-29')],
            [new \DateTime('2025-09-21'), -1, new \DateTime('2025-03-30')],
            [new \DateTime('2025-05-10'), -1, new \DateTime('2024-12-29')],
            [new \DateTime('2025-01-29'), -1, new \DateTime('2024-09-29')],
      ];
    }

    /**
     * @dataProvider dataGetStartOfQuarterOffset
     */
    public function testGetStartOfQuarterOffset(\DateTime $currentDate, int $deltaQuarter, \DateTime $expectedDate): void
    {
        $helper =  new IrhsQuarterHelper();
        $actualDate = $helper->getStartOfQuarterOffset($deltaQuarter, $currentDate);

        $this->assertEquals($expectedDate, $actualDate);
    }


    public function dataGetDateRangeForYearAndQuarter(): array
    {
        return [
            [2022, 1, ['2021/12/26', '2022/03/27']],
            [2024, 1, ['2023/12/31', '2024/03/31']],

            [2020, 4, ['2020/09/27', '2020/12/27']],
            [2021, 1, ['2020/12/27', '2021/03/28']],

            [2023, 4, ['2023/10/01', '2023/12/31']],
        ];
    }

    /**
     * @dataProvider dataGetDateRangeForYearAndQuarter
     */
    public function testGetDateRangeForYearAndQuarter(int $year, int $quarter, array $expectedDateRange)
    {
        $helper =  new IrhsQuarterHelper();

        $expectedDateRange = array_map(fn($v) => new \DateTime($v), $expectedDateRange);
        $dateRange = $helper->getDateRangeForYearAndQuarter($year, $quarter);
        $this->assertEquals($expectedDateRange, $dateRange);
    }

    public function dataGetQuarterAndYear(): array
    {
        return [
            ['2021/12/27', 1, 2022],
            ['2022/03/27', 2, 2022],

            ['2024/01/01', 1, 2024],
            ['2024/03/31', 2, 2024],

            ['2020/09/28', 4, 2020],
            ['2020/12/27', 1, 2021],

            ['2020/12/28', 1, 2021],
            ['2021/03/28', 2, 2021],

            ['2023/09/25', 3, 2023],
            ['2023/12/31', 1, 2024],
        ];
    }

    /**
     * @dataProvider dataGetQuarterAndYear
     */
    public function testGetQuarterAndYear(string $date, int $expectedQuarter, int $expectedYear)
    {
        $helper =  new IrhsQuarterHelper();
        $quarterAndYear = $helper->getQuarterAndYear(new \DateTime($date));
        $this->assertEquals([$expectedQuarter, $expectedYear], $quarterAndYear);
    }
}
