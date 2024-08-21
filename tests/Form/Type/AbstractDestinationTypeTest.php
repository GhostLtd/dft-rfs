<?php

namespace App\Tests\Form\Type;

use App\Form\DomesticSurvey\DayStop\CargoTypeType;
use App\Form\DomesticSurvey\DayStop\DestinationType;
use App\Tests\Form\Type\AbstractTypeTest;

abstract class AbstractDestinationTypeTest extends AbstractTypeTest
{
    abstract protected function getDataClass(): string;
    abstract protected function getFormClass(): string;

    protected function dataSubmit(): array
    {
        return [
            [[], false],
            [['banana' => '123'], false],

            [['destinationLocation' => 'Southampton', 'goodsUnloaded' => '0'], true],
            [['destinationLocation' => 'Southampton', 'goodsUnloaded' => '1', 'goodsTransferredTo' => '0'], true],
            [['destinationLocation' => 'Southampton', 'goodsUnloaded' => '1', 'goodsTransferredTo' => '1'], true],
            [['destinationLocation' => 'Southampton', 'goodsUnloaded' => '1', 'goodsTransferredTo' => '2'], true],
            [['destinationLocation' => 'Southampton', 'goodsUnloaded' => '1', 'goodsTransferredTo' => '4'], true],

            [['destinationLocation' => 'Southampton', 'goodsUnloaded' => '2', 'goodsTransferredTo' => '4'], false],
            [['destinationLocation' => 'Southampton', 'goodsUnloaded' => '1', 'goodsTransferredTo' => '3'], false],
            [['destinationLocation' => '', 'goodsUnloaded' => '1', 'goodsTransferredTo' => '4'], false],
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
            $this->assertEquals($formData['destinationLocation'] ?? null, $data->getDestinationLocation());
            $this->assertEquals($formData['goodsUnloaded'] ?? null, $data->getGoodsUnloaded());
            $this->assertEquals($formData['goodsTransferredTo'] ?? null, $data->getGoodsTransferredTo());
        }
    }
}
