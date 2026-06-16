<?php

namespace App\Tests\Form\Type\Domestic\CreateDay;

use App\Form\DomesticSurvey\CreateDay\NumberOfStopsType;
use App\Tests\Form\Type\AbstractTypeTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class NumberOfStopsTypeTest extends AbstractTypeTestCase
{
    public static function dataSubmit(): array
    {
        return [
            [[], false],
            [['banana' => '123'], false],

            [['hasMoreThanFiveStops' => '1'], true],
            [['hasMoreThanFiveStops' => '0'], true],
            [['hasMoreThanFiveStops' => 'abc'], false],
        ];
    }

    #[DataProvider('dataSubmit')]
    public function testSubmit(array $formData, bool $expectedValid): void
    {
        $form = $this->factory->create(NumberOfStopsType::class, null, ['day_number' => 2]);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isSubmitted());

        $this->assertEquals($expectedValid, $form->isValid());
    }
}