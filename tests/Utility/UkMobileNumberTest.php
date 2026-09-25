<?php

namespace App\Tests\Utility;

use App\Utility\UkMobileNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UkMobileNumberTest extends TestCase
{
    #[DataProvider('numbers')]
    public function testNormalise(?string $input, ?string $expected): void
    {
        self::assertSame($expected, UkMobileNumber::normalise($input));
    }

    public static function numbers(): array
    {
        return [
            'plain' => ['07700900123', '07700900123'],
            'spaced' => ['07700 900 123', '07700900123'],
            'dashes and dots' => ['07700-900.123', '07700900123'],
            'bracketed prefix' => ['(07700) 900123', '07700900123'],
            '+44' => ['+447700900123', '07700900123'],
            '+44 with (0)' => ['+44 (0)7700 900123', '07700900123'],
            '+44 with unbracketed 0' => ['+44 07700 900123', '07700900123'],
            '0044' => ['0044 7700 900123', '07700900123'],
            '0044 with unbracketed 0' => ['0044 07700 900123', '07700900123'],
            '44 without plus' => ['447700900123', '07700900123'],
            'Isle of Man 07624' => ['+44 7624 123456', '07624123456'],
            'surrounding whitespace' => ["  07700900123\t", '07700900123'],
            '070 personal number' => ['07012345678', null],
            '076 pager' => ['07612345678', null],
            'landline' => ['01225 123456', null],
            '+44 landline' => ['+44 01225 123456', null],
            'too short' => ['0770090012', null],
            'too long' => ['077009001234', null],
            'foreign mobile' => ['+33 6 12 34 56 78', null],
            'with trailing text' => ['07700 900123 (mobile)', null],
            'two numbers' => ['07700 900123 / 01225 123456', null],
            'free text' => ['call reception', null],
            'empty' => ['', null],
            'null' => [null, null],
        ];
    }
}
