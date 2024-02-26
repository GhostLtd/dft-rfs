<?php

namespace App\Tests\Form\Type\Domestic\ClosingDetails;

use App\Form\DomesticSurvey\ClosingDetails\EarlyResponseType;
use App\Tests\Form\Type\AbstractTypeTest;

class EarlyResponseTypeTest extends AbstractTypeTest
{
    protected function dataSubmit(): array
    {
        return [
            [[], false],
            [['banana' => '123'], false],

            [['is_correct' => 'true'], true],
            [['is_correct' => 'false'], true],
            [['is_correct' => 'abc'], false],
            [['is_correct' => '123'], false],
        ];
    }

    /**
     * @dataProvider dataSubmit
     */
    public function testSubmit(array $formData, bool $expectedValid): void
    {
        $form = $this->factory->create(EarlyResponseType::class);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedValid, $form->isValid());
    }
}
