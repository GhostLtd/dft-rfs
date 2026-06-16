<?php

namespace App\Tests\Utility\Form;

use App\Utility\Form\UnitStripper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UnitStripperTest extends TestCase
{
    public static function provider(): array
    {
        return [
            'space before unit' => ['3,500 kg', ['kg'], '3,500'],
            'no space before unit' => ['3500kg', ['kg'], '3500'],
            'uppercase unit' => ['3500 KG', ['kg'], '3500'],
            'trailing whitespace after unit' => ['3500 kg ', ['kg'], '3500'],
            'unit not in list untouched' => ['3500cc', ['kg'], '3500cc'],
            'longest unit wins' => ['5kg', ['g', 'kg'], '5'],
            'no units configured' => ['3500kg', [], '3500kg'],
            'no matching unit' => ['3500', ['kg'], '3500'],
            'empty string' => ['', ['kg'], ''],
        ];
    }

    /**
     * @param string[] $units
     */
    #[DataProvider('provider')]
    public function testStrip(string $input, array $units, string $expected): void
    {
        $this->assertSame($expected, (new UnitStripper())->strip($input, $units));
    }

    public function testNullPassesThrough(): void
    {
        $this->assertNull((new UnitStripper())->strip(null, ['kg']));
    }
}
