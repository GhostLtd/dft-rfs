<?php

namespace App\Tests\Form\Type;

use App\Form\InternationalSurvey\Action\HazardousGoodsType;
use App\Tests\Form\Type\AbstractTypeTest;
use App\Utility\HazardousGoodsHelper;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractHazardousGoodsTypeTest extends AbstractTypeTest
{
    abstract protected function getDataClass(): string;
    abstract protected function getFormClass(): string;

    #[\Override]
    protected function getExtensions(): array
    {
        $formClass = $this->getFormClass();

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnCallback(fn($s) => $s);

        return array_merge(
            parent::getExtensions(),
            [
                new PreloadedExtension([
                    new $formClass(new HazardousGoodsHelper($translator)),
                ], []),
            ]
        );
    }

    protected function dataSubmit(): array
    {
        return [
            [[], false],
            [['banana' => '123'], false],

            [['isHazardousGoods' => 'no'], true],
            [['isHazardousGoods' => 'yes'], false], // Need to fill other field
            [['isHazardousGoods' => 'banana'], false],

            [['isHazardousGoods' => 'yes', 'hazardousGoodsCode' => '1'], true],
            [['isHazardousGoods' => 'yes', 'hazardousGoodsCode' => '2.1'], true],
            [['isHazardousGoods' => 'yes', 'hazardousGoodsCode' => '2.2'], true],
            [['isHazardousGoods' => 'yes', 'hazardousGoodsCode' => '2.3'], true],
            [['isHazardousGoods' => 'yes', 'hazardousGoodsCode' => '3'], true],
            [['isHazardousGoods' => 'yes', 'hazardousGoodsCode' => '4.1'], true],
            [['isHazardousGoods' => 'yes', 'hazardousGoodsCode' => '4.2'], true],
            [['isHazardousGoods' => 'yes', 'hazardousGoodsCode' => '4.3'], true],
            [['isHazardousGoods' => 'yes', 'hazardousGoodsCode' => '5.1'], true],
            [['isHazardousGoods' => 'yes', 'hazardousGoodsCode' => '5.2'], true],
            [['isHazardousGoods' => 'yes', 'hazardousGoodsCode' => '6.1'], true],
            [['isHazardousGoods' => 'yes', 'hazardousGoodsCode' => '6.2'], true],
            [['isHazardousGoods' => 'yes', 'hazardousGoodsCode' => '7'], true],
            [['isHazardousGoods' => 'yes', 'hazardousGoodsCode' => '8'], true],
            [['isHazardousGoods' => 'yes', 'hazardousGoodsCode' => '9'], true],

            [['isHazardousGoods' => 'yes', 'hazardousGoodsCode' => '0'], false],
            [['isHazardousGoods' => 'yes', 'hazardousGoodsCode' => '1.1'], false],
            [['isHazardousGoods' => 'yes', 'hazardousGoodsCode' => '2'], false],
            [['isHazardousGoods' => 'yes', 'hazardousGoodsCode' => '2.4'], false],
            [['isHazardousGoods' => 'yes', 'hazardousGoodsCode' => '3.1'], false],
            [['isHazardousGoods' => 'yes', 'hazardousGoodsCode' => '4'], false],
            [['isHazardousGoods' => 'yes', 'hazardousGoodsCode' => '4.4'], false],
            [['isHazardousGoods' => 'yes', 'hazardousGoodsCode' => '5'], false],
            [['isHazardousGoods' => 'yes', 'hazardousGoodsCode' => '5.3'], false],
            [['isHazardousGoods' => 'yes', 'hazardousGoodsCode' => '6'], false],
            [['isHazardousGoods' => 'yes', 'hazardousGoodsCode' => '6.3'], false],
            [['isHazardousGoods' => 'yes', 'hazardousGoodsCode' => '7.1'], false],
            [['isHazardousGoods' => 'yes', 'hazardousGoodsCode' => '8.1'], false],
            [['isHazardousGoods' => 'yes', 'hazardousGoodsCode' => '9.1'], false],
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
            $isHazardousGoods = ($formData['isHazardousGoods'] ?? null) === 'yes';

            $hazardousGoodsCode = $isHazardousGoods ?
                ($formData['hazardousGoodsCode'] ?? null) :
                '0';

            $this->assertEquals($hazardousGoodsCode, $data->getHazardousGoodsCode());
        }
    }
}
