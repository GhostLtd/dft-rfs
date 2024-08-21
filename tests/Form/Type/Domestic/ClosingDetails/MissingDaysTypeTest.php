<?php

namespace App\Tests\Form\Type\Domestic\ClosingDetails;

use App\Form\DomesticSurvey\ClosingDetails\MissingDaysType;
use App\Tests\Form\Type\AbstractTypeTest;

class MissingDaysTypeTest extends AbstractTypeTest
{
    protected function dataSubmit(): array
    {
        return [
            [[], false],
            [['banana' => '123'], false],

            [['missing_days' => '1'], true],
            [['missing_days' => '0'], true],
            [['missing_days' => 'abc'], false],
            [['missing_days' => '123'], false],
        ];
    }

    /**
     * @dataProvider dataSubmit
     */
    public function testSubmit(array $formData, bool $expectedValid): void
    {
        $form = $this->factory->create(MissingDaysType::class);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedValid, $form->isValid());
    }
}
