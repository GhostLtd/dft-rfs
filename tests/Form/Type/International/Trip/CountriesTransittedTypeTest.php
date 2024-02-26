<?php

namespace App\Tests\Form\Type\International\Trip;

use App\Entity\International\Trip;
use App\Form\InternationalSurvey\Trip\CountriesTransittedType;
use App\Tests\Form\Type\AbstractTypeTest;

class CountriesTransittedTypeTest extends AbstractTypeTest
{
    protected function dataSubmit(): array
    {
        return [
            [[], true], // Form is allowed to be submitted empty...
            [['banana' => '123'], false],

            [['countriesTransitted' => ['FR', 'BE', 'NL']], true],
            [['countriesTransitted' => ['FR', 'BE', 'NL'], 'countriesTransittedOther' => 'Banana'], true],

            // Incorrect country code...
            [['countriesTransitted' => ['FR', 'BE', 'AG']], false],
        ];
    }

    /**
     * @dataProvider dataSubmit
     */
    public function testSubmit(array $formData, bool $expectedValid): void
    {
        $data = (new Trip());

        $form = $this->factory->create(CountriesTransittedType::class, $data);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isSubmitted());

        $this->assertEquals($expectedValid, $form->isValid());

        if ($expectedValid) {
            $this->assertEquals($formData['countriesTransitted'] ?? [], $data->getCountriesTransitted());
            $this->assertEquals($formData['countriesTransittedOther'] ?? null, $data->getCountriesTransittedOther());
        }
    }
}
