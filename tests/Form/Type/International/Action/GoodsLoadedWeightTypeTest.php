<?php

namespace App\Tests\Form\Type\International\Action;

use App\Entity\Domestic\DaySummary;
use App\Entity\International\Action;
use App\Form\DomesticSurvey\DaySummary\DistanceTravelledType;
use App\Form\InternationalSurvey\Action\GoodsLoadedWeightType;
use App\Tests\Form\Type\AbstractTypeTest;

class GoodsLoadedWeightTypeTest extends AbstractTypeTest
{
    protected function dataSubmit(): array
    {
        return [
            [[], false],
            [['banana' => '123'], false],

            [['weightOfGoods' => ''], false],
            [['weightOfGoods' => 'banana'], false],
            [['weightOfGoods' => '-1'], false],
            [['weightOfGoods' => '0.1'], false],
            [['weightOfGoods' => '9999999999'], false],

            [['weightOfGoods' => '0'], true],
            [['weightOfGoods' => '10'], true],
            [['weightOfGoods' => '999999999'], true],
        ];
    }

    /**
     * @dataProvider dataSubmit
     */
    public function testSubmit(array $formData, bool $expectedValid, ?string $expectedWeight=null): void
    {
        $data = new Action();

        $form = $this->factory->create(GoodsLoadedWeightType::class, $data);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isSubmitted());

        $this->assertEquals($expectedValid, $form->isValid());

        if ($expectedValid) {
            $expectedWeight = isset($formData['weightOfGoods']) ? floatval($formData['weightOfGoods']) : null;
            $this->assertEquals($expectedWeight, $data->getWeightOfGoods());
        }
    }
}
