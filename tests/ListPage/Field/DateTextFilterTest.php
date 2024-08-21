<?php

namespace App\Tests\ListPage\Field;

use App\ListPage\Field\DateTextFilter;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class DateTextFilterTest extends TestCase
{
    public function dataNormaliseDate(): array
    {
        return [
            ['2022-08-11', '2022-08-11'], // YMD
            ['2022/08/11', '2022-08-11'],
            ['2022\\08\\11', '2022-08-11'],

            ['2022-08', '2022-08'], // YM
            ['2022/08', '2022-08'],
            ['2022\\08', '2022-08'],

            ['2022', '2022'], // Y

            ['11-08-2022', '2022-08-11'], // DMY
            ['11/08/2022', '2022-08-11'],
            ['11\\08\\2022', '2022-08-11'],

            ['08-2022', '2022-08'], // MY
            ['08/2022', '2022-08'],
            ['08\\2022', '2022-08'],

            // Non-sensical inputs - replace separators and hope
            ['02/2022/08', '02-2022-08'], // About as ambiguous as it gets
            ['2022/30/02', '2022-30-02'], // Silly month placement
            ['02/30/2022', '02-30-2022'], // Silly month placement
        ];
    }

    /**
     * @dataProvider dataNormaliseDate
     */
    public function testNormaliseDate(string $input, string $expectedOutput): void
    {
        $dateTextFilter = $this->createStub(DateTextFilter::class);

        $output = (new ReflectionClass(DateTextFilter::class))
            ->getMethod('normaliseDate')
            ->invokeArgs($dateTextFilter, [$input]);

        $this->assertEquals($expectedOutput, $output);
    }
}