<?php

namespace App\Tests\Form\Type;

use App\Tests\Form\Type\AbstractTypeTest;

abstract class AbstractCargoTypeTypeTest extends AbstractTypeTest
{
    abstract protected function getDataClass(): string;
    abstract protected function getFormClass(): string;

    protected function dataSubmit(): array
    {
        return [
            [[], false],
            [['banana' => '123'], false],

            [['cargoTypeCode' => 'LB'], true],
            [['cargoTypeCode' => 'SB'], true],
            [['cargoTypeCode' => 'LFC'], true],
            [['cargoTypeCode' => 'OFC'], true],
            [['cargoTypeCode' => 'PL'], true],
            [['cargoTypeCode' => 'PS'], true],
            [['cargoTypeCode' => 'NP'], true],
            [['cargoTypeCode' => 'RC'], true],
            [['cargoTypeCode' => 'OT'], true],

            [['cargoTypeCode' => '123'], false],
        ];
    }

    /**
     * @dataProvider dataSubmit
     */
    public function testSubmit(array $formData, bool $expectedValid): void
    {
        $dataClass =  $this->getDataClass();
        $data = new $dataClass();

        $form = $this->factory->create($this->getFormClass(), $data);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isSubmitted());

        $this->assertEquals($expectedValid, $form->isValid());

        if ($expectedValid) {
            $this->assertEquals($formData['cargoTypeCode'] ?? null, $data->getCargoTypeCode());
        }
    }
}
