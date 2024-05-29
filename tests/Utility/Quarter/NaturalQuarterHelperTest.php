<?php

namespace App\Tests\Utility\Quarter;

use App\Utility\Quarter\NaturalQuarterHelper;
use PHPUnit\Framework\TestCase;

class NaturalQuarterHelperTest extends TestCase
{
    public function dataGetStartOfQuarterOffset(): array
    {
        return [
            [new \DateTime('2024-11-01'), 0, new \DateTime('2024-10-01')],

            [new \DateTime('2024-11-01'), -1, new \DateTime('2024-07-01')],
            [new \DateTime('2024-09-21'), -1, new \DateTime('2024-04-01')],
            [new \DateTime('2024-05-10'), -1, new \DateTime('2024-01-01')],
            [new \DateTime('2024-01-29'), -1, new \DateTime('2023-10-01')],
      ];
    }

    /**
     * @dataProvider dataGetStartOfQuarterOffset
     */
    public function testGetStartOfQuarterOffset(\DateTime $currentDate, int $deltaQuarter, \DateTime $expectedDate): void
    {
        $helper =  new NaturalQuarterHelper();
        $actualDate = $helper->getStartOfQuarterOffset($deltaQuarter, $currentDate);

        $this->assertEquals($expectedDate, $actualDate);
    }

    public function dataGetDateRangeForYearAndQuarter(): array
    {
        return [
            [2022, 1, ['2022/01/01', '2022/04/01']],
            [2024, 1, ['2024/01/01', '2024/04/01']],

            [2020, 4, ['2020/10/01', '2021/01/01']],
            [2021, 1, ['2021/01/01', '2021/04/01']],

            [2023, 4, ['2023/10/01', '2024/01/01']],
        ];
    }

    /**
     * @dataProvider dataGetDateRangeForYearAndQuarter
     */
    public function testGetDateRangeForYearAndQuarter(int $year, int $quarter, array $expectedDateRange)
    {
        $helper =  new NaturalQuarterHelper();

        $expectedDateRange = array_map(fn($v) => new \DateTime($v), $expectedDateRange);
        $dateRange = $helper->getDateRangeForYearAndQuarter($year, $quarter);
        $this->assertEquals($expectedDateRange, $dateRange);
    }

    public function dataGetQuarterAndYear(): array
    {
        return [
            ['2021/12/27', 4, 2021],
            ['2022/03/27', 1, 2022],

            ['2024/01/01', 1, 2024],
            ['2024/03/31', 1, 2024],

            ['2020/09/28', 3, 2020],
            ['2020/12/27', 4, 2020],

            ['2020/12/28', 4, 2020],
            ['2021/03/28', 1, 2021],

            ['2023/09/25', 3, 2023],
            ['2023/12/31', 4, 2023],
        ];
    }

    /**
     * @dataProvider dataGetQuarterAndYear
     */
    public function testGetQuarterAndYear(string $date, int $expectedQuarter, int $expectedYear)
    {
        $helper =  new NaturalQuarterHelper();
        $quarterAndYear = $helper->getQuarterAndYear(new \DateTime($date));
        $this->assertEquals([$expectedQuarter, $expectedYear], $quarterAndYear);
    }
}
