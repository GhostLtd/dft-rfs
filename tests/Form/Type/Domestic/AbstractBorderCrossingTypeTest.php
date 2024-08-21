<?php

namespace App\Tests\Form\Type\Domestic;

use App\Tests\Form\Type\AbstractTypeTest;

abstract class AbstractBorderCrossingTypeTest extends AbstractTypeTest
{
    abstract protected function getDataClass(): string;
    abstract protected function getFormClass(): string;

    protected function dataSubmit(): array
    {
        return [
            [[], false],
            [['banana' => '123'], false],

            [['borderCrossed' => '0'], true],
            [['borderCrossed' => '1', 'borderCrossingLocation' => 'Banana'], true],

            [['borderCrossed' => '1', 'borderCrossingLocation' => ''], false],
            [['borderCrossed' => '1'], false],
            [['borderCrossed' => 'abc'], false],
        ];
    }

    /**
     * @dataProvider dataSubmit
     */
    public function testSubmit(array $formData, bool $expectedValid): void
    {
        $dataClass = $this->getDataClass();
        $data = new $dataClass;
        $form = $this->factory->create($this->getFormClass(), $data);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isSubmitted());

        $this->assertEquals($expectedValid, $form->isValid());

        if ($expectedValid) {
            $expectedBorderCrossed = match($formData['borderCrossed']) {
                '1' => true,
                '0' => false,
                null => null,
            };

            $this->assertEquals($expectedBorderCrossed, $data->getBorderCrossed());
            $this->assertEquals($formData['borderCrossingLocation'] ?? null, $data->getBorderCrossingLocation());
        }
    }
}
