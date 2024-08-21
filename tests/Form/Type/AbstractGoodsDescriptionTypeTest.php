<?php

namespace App\Tests\Form\Type;

use App\Entity\GoodsDescriptionInterface;
use App\Tests\Form\Type\AbstractTypeTest;

abstract class AbstractGoodsDescriptionTypeTest extends AbstractTypeTest
{
    abstract protected function getDataClass(): string;
    abstract protected function getFormClass(): string;
    abstract protected function shouldIncludeEmpty(): bool;

    protected function dataSubmit(): array
    {
        $includeEmpty = $this->shouldIncludeEmpty();

        return [
            [[], false],
            [['banana' => '123'], false],

            [['goodsDescription' => ''], false],
            [['goodsDescription' => 'empty'], $includeEmpty],
            [['goodsDescription' => 'groupage'], true],
            [['goodsDescription' => 'other-goods', 'goodsDescriptionOther' => 'Bananas'], true],

            [['goodsDescription' => 'other-goods'], false],
            [['goodsDescription' => 'other-goods', 'goodsDescriptionOther' => ''], false],
        ];
    }

    /**
     * @dataProvider dataSubmit
     */
    public function testSubmit(array $formData, bool $expectedValid): void
    {
        $dataClass = $this->getDataClass();
        $data = new $dataClass();
        $this->assertInstanceOf(GoodsDescriptionInterface::class, $data);

        $form = $this->factory->create($this->getFormClass(), $data);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isSubmitted());

        $this->assertEquals($expectedValid, $form->isValid());

        if ($expectedValid) {
            $this->assertEquals($formData['goodsDescription'] ?? null, $data->getGoodsDescription());
            $this->assertEquals($formData['goodsDescriptionOther'] ?? null, $data->getGoodsDescriptionOther());
        }
    }
}
