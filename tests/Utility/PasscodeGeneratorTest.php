<?php


namespace App\Tests\Utility;


use App\Utility\PasscodeGenerator;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;

class PasscodeGeneratorTest extends TestCase
{
    public function repeatingFixtureProvider()
    {
        return [
            [true, "01235555", 4],
            [true, "55550123", 4],
            [true, "01555534", 4],
            [true, "01234555", 3],
            [true, "55501234", 3],
            [true, "01255534", 3],

            [false, "01234555", 4],
            [false, "55501234", 4],
            [false, "01255534", 4],
            [false, "01234555", 4],
            [false, "55501234", 4],
            [false, "01255534", 4],
        ];
    }

    public function sequentialFixtureProvider()
    {
        return [
            [true, "12349797", 4],
            [true, "97971234", 4],
            [true, "97123497", 4],
            [true, "43219797", 4],
            [true, "97974321", 4],
            [true, "97432197", 4],

            [false, "12397979", 4],
            [false, "97979123", 4],
            [false, "97123979", 4],
            [false, "12304979", 4],
            [false, "32197979", 4],
            [false, "97979321", 4],
            [false, "97932197", 4],
        ];
    }

    public function passcodesFixtureProvider()
    {
        return [
            [true, "12312312"],
            [true, "32132132"],
            [true, "55502020"],
            [true, "02020555"],
            [true, "02055502"],
            [true, "12355500"],

            [false, "12340202"],
            [false, "02021234"],
            [false, "02123402"],
            [false, "97555597"],
            [false, "55559797"],
            [false, "97975555"],

            // zero pad
            [false, "1030507"],
            [true, "01030507"],
        ];
    }

    /**
     * @dataProvider repeatingFixtureProvider
     * @param $passcode
     * @param $repeatCount
     * @throws ReflectionException
     */
    public function testRepeatingDigits($isRepeating, $passcode, $repeatCount)
    {
        $pc = new ReflectionClass(new PasscodeGenerator());
        $method = $pc->getMethod('hasRepeatingDigits');
        $method->setAccessible(true);

        $this->assertEquals($isRepeating, $method->invoke(new PasscodeGenerator(), $passcode, $repeatCount));
    }

    /**
     * @dataProvider sequentialFixtureProvider
     * @param $isSequential
     * @param $passcode
     * @param $repeatCount
     * @throws ReflectionException
     */
    public function testSequentialDigits($isSequential, $passcode, $repeatCount)
    {
        $pc = new ReflectionClass(new PasscodeGenerator());
        $method = $pc->getMethod('hasSequentialDigits');
        $method->setAccessible(true);

        $this->assertEquals($isSequential, $method->invoke(new PasscodeGenerator(), $passcode, $repeatCount));
    }

    /**
     * @param $isValid
     * @param $passcode
     * @dataProvider passcodesFixtureProvider
     */
    public function testPasscodeValidator($isValid, $passcode)
    {
        $pc = new PasscodeGenerator();
        $this->assertEquals($isValid, $pc->isValidPasscode($passcode));
    }

}