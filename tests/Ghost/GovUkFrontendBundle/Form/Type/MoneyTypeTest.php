<?php

namespace App\Tests\Ghost\GovUkFrontendBundle\Form\Type;

use Ghost\GovUkFrontendBundle\Form\DataTransformer\CostTransformer;
use PHPUnit\Framework\TestCase;

class MoneyTypeTest extends TestCase
{
    public function transformData(): array
    {
        return [
            [null, ''],
            [0, '0.00'],
            [700, '7.00'],
            [2, '0.02'],
            [502, '5.02'],
            [50, '0.50'],
            [220, '2.20'],
            [72, '0.72'],
            [123, '1.23'],

            [0, '0', 0],
            [3, '3', 0],
            [30, '30', 0],

            [0, '0.000', 3],
            [2, '0.002', 3],
            [1002, '1.002', 3],
            [20, '0.020', 3],
            [200, '0.200', 3],
        ];
    }

    /**
     * @dataProvider transformData
     */
    public function testTransformer($intValue, $stringValue, $decimalPlaces = 2)
    {
        $transformer = new CostTransformer(pow(10, $decimalPlaces));
        self::assertSame($stringValue, $transformer->transform($intValue));
        self::assertSame($intValue, $transformer->reverseTransform($stringValue));
    }
}
