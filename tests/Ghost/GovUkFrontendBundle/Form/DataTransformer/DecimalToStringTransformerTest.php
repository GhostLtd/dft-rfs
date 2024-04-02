<?php

namespace App\Tests\Ghost\GovUkFrontendBundle\Form\DataTransformer;

use Ghost\GovUkFrontendBundle\Form\DataTransformer\DecimalToStringTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

class DecimalToStringTransformerTest extends TestCase
{
    public function transformData(): array
    {
        return [
            // Empty
            [true, '', ''],
            [true, null, ''],

            // Valid values
            [true, '2', '2'],
            [true, '-2', '-2'],
            [true, '12345', '12345'],
            [true, '-12345', '-12345'],
            [true, '-12345.6', '-12345.6'],
            [true, '123456.78', '123456.78'],

            [true, '123456.00', '123456'],
            [true, '-123456.00', '-123456'],

            [true, '123456.7891', '123456.7891', ['precision' => 10, 'scale' => 4]],
            [true, '-123456.7891', '-123456.7891', ['precision' => 10, 'scale' => 4]],
            [true, '12345', '12345', ['precision' => 5, 'scale' => 0]],
            [true, '-12345', '-12345', ['precision' => 5, 'scale' => 0]],

            [true, '123456.7800', '123456.78', ['precision' => 10, 'scale' => 4]],
            [true, '-123456.7800', '-123456.78', ['precision' => 10, 'scale' => 4]],

            // Invalid values
            [false, '1234567.89'],
            [false, '1234567.8'],
            [false, '1234567'],
            [false, '-1234567'],
            [false, '-1234567.8'],
            [false, '-1234567.89'],
            [false, '1.234'],
            [false, '-1.234'],
            [false, 'banana'],
            [false, '1e10'],
            [false, '1^2'],
            [false, '1+3'],

            [false, '123456.78901', null, ['precision' => 10, 'scale' => 4]],
            [false, '-123456.78901', null, ['precision' => 10, 'scale' => 4]],
            [false, '1234567.1', null, ['precision' => 10, 'scale' => 4]],
            [false, '-1234567.1', null, ['precision' => 10, 'scale' => 4]],
            [false, '1234567', null, ['precision' => 10, 'scale' => 4]],
            [false, '-1234567', null, ['precision' => 10, 'scale' => 4]],
            [false, '123456', null, ['precision' => 5, 'scale' => 0]],
            [false, '-123456', null, ['precision' => 5, 'scale' => 0]],
            [false, '12345.1', null, ['precision' => 5, 'scale' => 0]],
            [false, '-12345.1', null, ['precision' => 5, 'scale' => 0]],
        ];
    }

    /**
     * @dataProvider transformData
     */
    public function testTransform(bool $expectedValid, mixed $decimalValue, ?string $expectedTransformedValue=null, array $options=[]): void
    {
        if (!$expectedValid) {
            $this->expectException(TransformationFailedException::class);
        }

        $transformer = new DecimalToStringTransformer(...$options);
        self::assertSame($expectedTransformedValue, $transformer->transform($decimalValue));
    }

    public function reverseTransformData(): array
    {
        return [
            // Empty values
            [true, '', null],
            [true, null, null],

            // Valid values
            [true, '2', '2.00'],
            [true, '-2', '-2.00'],
            [true, '12345', '12345.00'],
            [true, '-12345', '-12345.00'],
            [true, '-12345.6', '-12345.60'],
            [true, '123456.78', '123456.78'],

            [true, '123456', '123456.00'],
            [true, '-123456', '-123456.00'],

            [true, '123456.7891', '123456.7891', ['precision' => 10, 'scale' => 4]],
            [true, '-123456.7891', '-123456.7891', ['precision' => 10, 'scale' => 4]],
            [true, '12345', '12345', ['precision' => 5, 'scale' => 0]],
            [true, '-12345', '-12345', ['precision' => 5, 'scale' => 0]],

            [true, '123456.78', '123456.7800', ['precision' => 10, 'scale' => 4]],
            [true, '-123456.78', '-123456.7800', ['precision' => 10, 'scale' => 4]],

            // Invalid values
            [false, '1234567.89'],
            [false, '1234567.8'],
            [false, '1234567'],
            [false, '-1234567'],
            [false, '-1234567.8'],
            [false, '-1234567.89'],
            [false, '1.234'],
            [false, '-1.234'],
            [false, 'banana'],
            [false, '1e10'],
            [false, '1^2'],
            [false, '1+3'],

            [false, '123456.78901', null, ['precision' => 10, 'scale' => 4]],
            [false, '-123456.78901', null, ['precision' => 10, 'scale' => 4]],
            [false, '1234567.1', null, ['precision' => 10, 'scale' => 4]],
            [false, '-1234567.1', null, ['precision' => 10, 'scale' => 4]],
            [false, '1234567', null, ['precision' => 10, 'scale' => 4]],
            [false, '-1234567', null, ['precision' => 10, 'scale' => 4]],
            [false, '123456', null, ['precision' => 5, 'scale' => 0]],
            [false, '-123456', null, ['precision' => 5, 'scale' => 0]],
            [false, '12345.1', null, ['precision' => 5, 'scale' => 0]],
            [false, '-12345.1', null, ['precision' => 5, 'scale' => 0]],
        ];
    }

    /**
     * @dataProvider reverseTransformData
     */
    public function testReverseTransform(bool $expectedValid, ?string $stringValue, ?string $expectedDecimalValue=null, array $options=[]): void
    {
        if (!$expectedValid) {
            $this->expectException(TransformationFailedException::class);
        }

        $transformer = new DecimalToStringTransformer(...$options);
        self::assertEquals($expectedDecimalValue, $transformer->reverseTransform($stringValue));
    }
}
