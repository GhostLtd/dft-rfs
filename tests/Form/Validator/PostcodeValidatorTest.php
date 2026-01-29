<?php

namespace App\Tests\Form\Validator;

use App\Form\Validator\Postcode;
use App\Form\Validator\PostcodeValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class PostcodeValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): PostcodeValidator
    {
        return new PostcodeValidator();
    }

    public function dataAllowPartialValid(): array
    {
        return [
            'full with space' => ['NN6 6EA'],
            'full no space' => ['NN66EA'],
            'outcode only (letter-digit-digit)' => ['NN6'],
            'outcode only (letter-digit)' => ['L1'],
            'outcode only (letter-letter-digit-digit)' => ['PO16'],
            'outcode only (letter-digit-letter)' => ['A9A'],
            'outcode only (letter-letter-digit-letter)' => ['SW1W'],
            'full lowercase' => ['nn6 6ea'],
            'outcode lowercase' => ['nn6'],
        ];
    }

    /** @dataProvider dataAllowPartialValid */
    public function testAllowPartialValid(string $value): void
    {
        $this->validator->validate($value, new Postcode(allowPartial: true));
        $this->assertNoViolation();
    }

    public function dataAllowPartialInvalid(): array
    {
        return [
            'incomplete incode' => ['NN66E'],
            'outcode + garbage' => ['NN6 banana'],
            'repeated outcode' => ['NN6 NN6 NN6'],
            'plain text' => ['Sheffield'],
            'number only' => ['75'],
            'number + text' => ['75 miles'],
        ];
    }

    /** @dataProvider dataAllowPartialInvalid */
    public function testAllowPartialInvalid(string $value): void
    {
        $this->validator->validate($value, new Postcode(allowPartial: true));
        $this->buildViolation('common.address.postcode.invalid')->assertRaised();
    }

    public function testDefaultRejectsOutcodeOnly(): void
    {
        $this->validator->validate('NN6', new Postcode());
        $this->buildViolation('common.address.postcode.invalid')->assertRaised();
    }

    public function testDefaultAcceptsFullPostcode(): void
    {
        $this->validator->validate('NN6 6EA', new Postcode());
        $this->assertNoViolation();
    }

    public function testNullValueSkipsValidation(): void
    {
        $this->validator->validate(null, new Postcode(allowPartial: true));
        $this->assertNoViolation();
    }

    public function testEmptyStringSkipsValidation(): void
    {
        $this->validator->validate('', new Postcode(allowPartial: true));
        $this->assertNoViolation();
    }
}
