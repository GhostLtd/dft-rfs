<?php

namespace App\Tests\Utility;

use App\Utility\RegistrationMarkHelper;
use PHPUnit\Framework\TestCase;

class RegistrationMarkHelperTest extends TestCase
{
    public function dataProvider()
    {
        return [
            // ## Valid Current Style
            ["AB51ABC", true, true, "AB51 ABC"],

            // ## Invalid Current Style
            // Missing First Letter = Prefix e.g. A51ABC
            ["A51ABC", true, false, "A51ABC"],
            // Extra First Letter
            ["ABC54ABC", false, false, null],
            // Missing Number
            ["AB5ABC", false, false, null],
            // Extra Number
            ["AB543ABC", false, false, null],
            // Missing Last Letter
            ["AB54AB", false, false, null],
            // Extra Last Letter
            ["AB54ABCD", false, false, null],

            // ## Valid Prefix
            ["A123ABC", true, false, "A123ABC"],
            ["A12ABC", true, false, "A12ABC"],
            ["A1ABC", true, false, "A1ABC"],

            // ## Invalid Prefix
            // Missing First Letter = Dateless e.g. 123ABC
            ["123ABC", true, false, "123ABC"],
            // Extra First Letter
            ["AB1ABC", false, false, null],
            // Missing Numbers
            ["AABC", false, false, null],
            // Extra Numbers
            ["A1234ABC", false, false, null],
            // Missing Last Letter
            ["A1AB", false, false, null],
            // Extra Last Letter
            ["A1ABCD", false, false, null],

            // ## Valid Suffix
            ["ABC123A", true, false, "ABC123A"],
            ["ABC12A", true, false, "ABC12A"],
            ["ABC1A", true, false, "ABC1A"],

            // ## Invalid Suffix
            // Missing First Letter
            ["AB1A", false, false, null],
            ["AB123A", false, false, null],
            // Extra First Letter
            ["ABCD1A", false, false, null],
            ["ABCD123A", false, false, null],
            // Missing Numbers
            ["ABCA", false, false, null],
            // Extra Numbers
            ["ABC1234A", false, false, null],
            // Missing Last Letter = Dateless e.g. ABC123
            ["ABC123", true, false, "ABC123"],
            // Extra Last Letter
            ["ABC1AB", false, false, null],

            // ## Valid Dateless
            ["1ABC", true, false, "1ABC"],
            ["ABC1", true, false, "ABC1"],
            ["1234A", true, false, "1234A"],
            ["A1234", true, false, "A1234"],
            ["1234AB", true, false, "1234AB"],
            ["AB1234", true, false, "AB1234"],
            ["123ABC", true, false, "123ABC"],
            ["ABC123", true, false, "ABC123"],

            // ## Invalid Dateless
            // Missing Numbers
            ["ABC", false, false, null],
            // Extra Numbers
            ["12345A", false, false, null],
            ["A12345", false, false, null],
            // Missing Letters
            ["1", false, false, null],
            ["123", false, false, null],
            ["1234", false, false, null],
            // Extra Letters
            ["1ABCD", false, false, null],
            ["ABCD1", false, false, null],
            // More than 6 characters
            ["1234ABC", false, false, null],
            ["ABCD123", false, false, null],

            // ## Valid Northern Ireland
            ["ABC123", true, false, "ABC123"],
            ["ABC1234", true, false, "ABC1234"],

            // ## Invalid Northern Ireland
            // Extra Letter
            ["ABCD123", false, false, null],
            // Extra Number
            ["ABC12345", false, false, null],

            // ## Valid Diplomatic
            ["101D234", true, false, "101D234"],
            ["123X456", true, false, "123X456"],

            // ## Invalid Diplomatic
            // Not D or X in middle
            ["123A456", false, false, null],
            // Extra numbers
            ["1234D567", false, false, null],
            ["123X4567", false, false, null],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testRepeatingDigits(string $registrationMark, bool $isValid, bool $isCurrent, ?string $expectedOutput): void
    {
        $helper = new RegistrationMarkHelper($registrationMark);

        $this->assertEquals($isValid, $helper->isValid());
        $this->assertEquals($isCurrent, $helper->isCurrent());
        $this->assertEquals($expectedOutput, $helper->getRegistrationMark());
    }
}