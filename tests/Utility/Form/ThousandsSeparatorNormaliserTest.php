<?php

namespace App\Tests\Utility\Form;

use App\Utility\Form\ThousandsSeparatorNormaliser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ThousandsSeparatorNormaliserTest extends TestCase
{
    public static function provider(): array
    {
        return [
            // Clearly thousands-separated → stripped
            'comma group' => ['1,000', '1000'],
            'dot group' => ['1.000', '1000'],
            'multiple comma groups' => ['188,188,188', '188188188'],
            'multiple dot groups' => ['188.188.188', '188188188'],
            'leading 1-3 digits' => ['44,000', '44000'],
            'two leading digits' => ['12,345,678', '12345678'],
            'surrounding whitespace trimmed' => ['  44,000  ', '44000'],

            // Ambiguous / not thousands grouping → unchanged
            'mixed separators' => ['188,188.188', '188,188.188'],
            'comma two trailing digits' => ['1,00', '1,00'],
            'dot two trailing digits' => ['1.00', '1.00'],
            'dot one trailing digit' => ['1.8', '1.8'],
            'comma one trailing digit' => ['1,8', '1,8'],
            'uneven final group' => ['1,000,00', '1,000,00'],
            'non-three grouping' => ['12,34,567', '12,34,567'],
            'plain integer untouched' => ['1000', '1000'],

            // Edge passthrough
            'empty string' => ['', ''],
        ];
    }

    #[DataProvider('provider')]
    public function testNormalise(string $input, string $expected): void
    {
        $this->assertSame($expected, (new ThousandsSeparatorNormaliser())->normalise($input));
    }

    public function testNullPassesThrough(): void
    {
        $this->assertNull((new ThousandsSeparatorNormaliser())->normalise(null));
    }

    public function testZeroGroupStrips(): void
    {
        // Documented edge: "0,000" → "0000" (later rejected by NotBlank/Range), not silently kept.
        $this->assertSame('0000', (new ThousandsSeparatorNormaliser())->normalise('0,000'));
    }
}
