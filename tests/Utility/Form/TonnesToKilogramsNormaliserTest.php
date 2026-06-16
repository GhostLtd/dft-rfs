<?php

namespace App\Tests\Utility\Form;

use App\Utility\Form\TonnesToKilogramsNormaliser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TonnesToKilogramsNormaliserTest extends TestCase
{
    public static function provider(): array
    {
        return [
            // Converted: explicit tonnes unit + value < 100
            'dot decimal, T' => ['3.8 T', '3800'],
            'comma decimal, TON' => ['3,5 TON', '3500'],
            'whole number, T' => ['44 T', '44000'],
            'word tonnes' => ['26.5 tonnes', '26500'],
            'lowercase t no space' => ['7.5t', '7500'],
            'two decimals' => ['3.55 t', '3550'],
            'two decimals comma' => ['3,50 TON', '3500'],
            'tons plural' => ['26.5 tons', '26500'],
            'just under threshold' => ['99 T', '99000'],

            // Left unchanged
            'three decimals are ambiguous (could be a thousands group)' => ['3,500 T', '3,500 T'],
            'three decimals not converted' => ['3.825 t', '3.825 t'],
            'at threshold (not < 100)' => ['100 T', '100 T'],
            'above threshold' => ['150 TON', '150 TON'],
            'no unit, decimal' => ['3.8', '3.8'],
            'no unit, integer' => ['3800', '3800'],
            'kg is not a tonnes unit' => ['3.8 kg', '3.8 kg'],
            'unit only' => ['T', 'T'],
            'non-numeric' => ['abc t', 'abc t'],
            'empty string' => ['', ''],
        ];
    }

    #[DataProvider('provider')]
    public function testNormalise(string $input, string $expected): void
    {
        $this->assertSame($expected, (new TonnesToKilogramsNormaliser())->normalise($input));
    }

    public function testNullPassesThrough(): void
    {
        $this->assertNull((new TonnesToKilogramsNormaliser())->normalise(null));
    }

    public function testWholeNumberTonnesStillConvert(): void
    {
        // A fractional part is restricted to 1–2 dp, but whole tonnes are the clearest case.
        $this->assertSame('44000', (new TonnesToKilogramsNormaliser())->normalise('44 T'));
    }
}
