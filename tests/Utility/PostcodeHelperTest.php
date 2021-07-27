<?php

namespace App\Tests\Utility;

use App\Utility\PostcodeHelper;
use PHPUnit\Framework\TestCase;

class PostcodeHelperTest extends TestCase
{
    public function validPostcodeDataProvider(): array
    {
        return [
            // 1. With spaces
            ['SW1W 0NY', true],
            ['A9A 9AA', true],
            ['L1 8JQ', true],
            ['P16 7GZ', true],
            ['PO1 7GZ', true],
            ['PO16 7GZ', true],
            ['GU16 7HF', true],
            ['JE2 3FJ', true],  // Jersey
            ['GY10 1PB', true], // Sark
            ['GX11 1AA', true], // Gibraltar
            ['GY1 1AN', true],  // Guernsey
            ['IM4 7HB', true],  // Isle of man
            ['GY9 3AD', true],  // Alderney
            ['9A 8AB', false],
            ['Z1A 0B1', false],
            ['A1A 0B11', false],

            // 2. Without spaces
            ['SW1W0NY', true],
            ['A9A9AA', true],
            ['L18JQ', true],
            ['P167GZ', true],
            ['PO17GZ', true],
            ['PO167GZ', true],
            ['GU167HF', true],
            ['JE23FJ', true],
            ['GY101PB', true],
            ['GX111AA', true],
            ['GY11AN', true],
            ['IM47HB', true],
            ['GY93AD', true],
            ['9A8AB', false],
            ['Z1A0B1', false],
            ['A1A0B11', false],

            // 3. Non-postcodes
            ['Swindon', false],
            ['75', false],
            ['75 miles', false],
            ['75 Oxford Street', false],
        ];
    }

    /**
     * @dataProvider validPostcodeDataProvider
     */
    public function testValidPostcode(string $potentialPostcode, bool $isValid): void
    {
        $this->assertEquals($isValid, PostcodeHelper::isValidPostcode($potentialPostcode));
    }

    public function fullPostcodeFormattingDataProvider(): array
    {
        return [
            ['SW1W0NY', 'SW1W 0NY'],
            ['A9A9AA', 'A9A 9AA'],
            ['L18JQ', 'L1 8JQ'],
            ['P167GZ', 'P16 7GZ'],
            ['PO17GZ', 'PO1 7GZ'],
            ['PO167GZ', 'PO16 7GZ'],
            ['GU167HF', 'GU16 7HF'],
            ['JE23FJ', 'JE2 3FJ'],
            ['GY101PB', 'GY10 1PB'],
            ['GX111AA', 'GX11 1AA'],
            ['GY11AN', 'GY1 1AN'],
            ['IM47HB', 'IM4 7HB'],
            ['GY93AD', 'GY9 3AD'],
            ['Po17gZ', 'PO1 7GZ'],
            ['Po167gZ', 'PO16 7GZ'],

            ['sw1', 'sw1'],
            ['a9a', 'a9a'],
            ['l1', 'l1'],
            ['p16', 'p16'],
            ['po1', 'po1'],
            ['po16', 'po16'],
            ['gU16', 'gU16'],
            ['Po221', 'Po221'],
            ['9A 8AB', '9A 8AB'],
            ['Z1A 0B1', 'Z1A 0B1'],
            ['A1A 0B11', 'A1A 0B11'],
            ['Swindon', 'Swindon'],
        ];
    }

    /**
     * @dataProvider fullPostcodeFormattingDataProvider
     */
    public function testFullPostcodeFormatting(string $potentialPostcode, string $expectedOutput): void
    {
        $this->assertEquals($expectedOutput, PostcodeHelper::formatIfPostcode($potentialPostcode));
    }

    public function partialPostcodeFormattingDataProvider(): array
    {
        return [
            ['SW1W0NY', 'SW1W 0NY'],
            ['A9A9AA', 'A9A 9AA'],
            ['L18JQ', 'L1 8JQ'],
            ['P167GZ', 'P16 7GZ'],
            ['PO17GZ', 'PO1 7GZ'],
            ['PO167GZ', 'PO16 7GZ'],
            ['GU167HF', 'GU16 7HF'],
            ['JE23FJ', 'JE2 3FJ'],
            ['GY101PB', 'GY10 1PB'],
            ['GX111AA', 'GX11 1AA'],
            ['GY11AN', 'GY1 1AN'],
            ['IM47HB', 'IM4 7HB'],
            ['GY93AD', 'GY9 3AD'],
            ['Po17gZ', 'PO1 7GZ'],
            ['Po167gZ', 'PO16 7GZ'],
            ['sw1', 'SW1'],
            ['a9a', 'A9A'],
            ['l1', 'L1'],
            ['p16', 'P16'],
            ['po1', 'PO1'],
            ['po16', 'PO16'],
            ['gU16', 'GU16'],

            ['Po221', 'Po221'],
            ['9A 8AB', '9A 8AB'],
            ['Z1A 0B1', 'Z1A 0B1'],
            ['A1A 0B11', 'A1A 0B11'],
            ['Swindon', 'Swindon'],
        ];
    }

    /**
     * @dataProvider partialPostcodeFormattingDataProvider
     */
    public function testPartialPostcodeFormatting(string $potentialPostcode, string $expectedOutput): void
    {
        $this->assertEquals($expectedOutput, PostcodeHelper::formatIfPostcode($potentialPostcode, true));
    }
}