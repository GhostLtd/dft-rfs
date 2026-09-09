<?php

namespace App\Tests\Form\Validator;

use App\Features;
use App\Form\Validator\Postcode;
use App\Form\Validator\PostcodeValidator;
use App\Utility\OutwardCodes\OutwardCodeChecker;
use Ghost\GovUkCoreBundle\Features as CoreFeatures;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class PostcodeValidatorTest extends ConstraintValidatorTestCase
{
    private ?string $dataFile = null;

    protected function createValidator(): PostcodeValidator
    {
        // A small outward-code data file: NN6 and SO14 are known districts, nothing else is
        $this->dataFile = tempnam(sys_get_temp_dir(), 'outward-codes-');
        file_put_contents($this->dataFile, "# test data\nNN1-6\nSO14-15\n");

        return new PostcodeValidator(new OutwardCodeChecker($this->dataFile));
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->setOutwardCodeCheckFeature(false);
    }

    #[\Override]
    protected function tearDown(): void
    {
        parent::tearDown();
        if ($this->dataFile !== null) {
            @unlink($this->dataFile);
        }
        $this->setOutwardCodeCheckFeature(false);
    }

    /**
     * Toggle OUTWARD_CODE_CHECK via $_ENV['APP_FEATURES'] and reset the bundle's static feature cache.
     */
    private function setOutwardCodeCheckFeature(bool $enabled): void
    {
        $_ENV['APP_FEATURES'] = $enabled ? Features::OUTWARD_CODE_CHECK : '';
        $prop = new \ReflectionProperty(CoreFeatures::class, 'enabledFeatures');
        $prop->setValue(null, []);
    }

    public static function dataAllowPartialValid(): array
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

    #[DataProvider('dataAllowPartialValid')]
    public function testAllowPartialValid(string $value): void
    {
        $this->validator->validate($value, new Postcode(allowPartial: true));
        $this->assertNoViolation();
    }

    public static function dataAllowPartialInvalid(): array
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

    #[DataProvider('dataAllowPartialInvalid')]
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

    // -----------------------------------------------------------------
    // checkOutwardCode
    // -----------------------------------------------------------------

    public static function dataOutwardCodeCheck(): array
    {
        return [
            'known district, full' => ['NN6 6EA', true],
            'known district, full, no space' => ['SO140YG', true],
            'known district, partial' => ['NN6', true],
            'known district, partial lowercase' => ['so14', true],
            'unknown district, full' => ['ZZ1 1AA', false],
            'unknown district, partial' => ['ZZ1', false],
            'unknown district in a known area' => ['NN7', false],
            'malformed still rejected' => ['NN6 banana', false],
        ];
    }

    #[DataProvider('dataOutwardCodeCheck')]
    public function testCheckOutwardCodeWhenFeatureEnabled(string $value, bool $expectedValid): void
    {
        $this->setOutwardCodeCheckFeature(true);

        $this->validator->validate($value, new Postcode(allowPartial: true, checkOutwardCode: true));

        if ($expectedValid) {
            $this->assertNoViolation();
        } else {
            $this->buildViolation('common.address.postcode.invalid')->assertRaised();
        }
    }

    public function testCheckOutwardCodeAppliesToFullPostcodesToo(): void
    {
        $this->setOutwardCodeCheckFeature(true);

        $this->validator->validate('ZZ1 1AA', new Postcode(checkOutwardCode: true));
        $this->buildViolation('common.address.postcode.invalid')->assertRaised();
    }

    public function testUnknownDistrictAcceptedWhenFeatureDisabled(): void
    {
        $this->setOutwardCodeCheckFeature(false);

        $this->validator->validate('ZZ1 1AA', new Postcode(allowPartial: true, checkOutwardCode: true));
        $this->assertNoViolation();
    }

    public function testUnknownDistrictAcceptedWhenConstraintDoesNotAskForTheCheck(): void
    {
        $this->setOutwardCodeCheckFeature(true);

        $this->validator->validate('ZZ1 1AA', new Postcode(allowPartial: true));
        $this->assertNoViolation();
    }
}
