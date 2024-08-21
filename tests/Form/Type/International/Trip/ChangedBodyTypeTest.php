<?php

namespace App\Tests\Form\Type\International\Trip;

use App\Entity\International\Trip;
use App\Entity\International\Vehicle;
use App\Form\InternationalSurvey\Trip\ChangedBodyTypeType;
use App\Tests\Form\Type\AbstractTypeTest;

class ChangedBodyTypeTest extends AbstractTypeTest
{
    protected function dataSubmit(): array
    {
        return [
            ['box', [], false],
            ['box', ['banana' => '123'], false],

            ['box', ['isChangedBodyType' => 'banana'],  false],
            ['box', ['isChangedBodyType' => 'yes'],  false],

            // If the body type hasn't been changed, then the bodyType on the trip is recorded as null
            ['flat-drop', ['isChangedBodyType' => 'no'], true, null],
            ['box', ['isChangedBodyType' => 'no'],  true, null],

            ['flat-drop', ['isChangedBodyType' => 'yes', 'bodyType' => 'box'], true, 'box'],
            ['flat-drop', ['isChangedBodyType' => 'yes', 'bodyType' => 'temperature-controlled'], true, 'temperature-controlled'],
            ['flat-drop', ['isChangedBodyType' => 'yes', 'bodyType' => 'curtain-sided'], true, 'curtain-sided'],
            ['flat-drop', ['isChangedBodyType' => 'yes', 'bodyType' => 'liquid'], true, 'liquid'],
            ['flat-drop', ['isChangedBodyType' => 'yes', 'bodyType' => 'solid-bulk'], true, 'solid-bulk'],
            ['flat-drop', ['isChangedBodyType' => 'yes', 'bodyType' => 'livestock'], true, 'livestock'],
            ['flat-drop', ['isChangedBodyType' => 'yes', 'bodyType' => 'car'], true, 'car'],
            ['flat-drop', ['isChangedBodyType' => 'yes', 'bodyType' => 'tipper'], true, 'tipper'],
            ['flat-drop', ['isChangedBodyType' => 'yes', 'bodyType' => 'other'], true, 'other'],

            // The original body selection isn't in the bodyType list...
            ['flat-drop', ['isChangedBodyType' => 'yes', 'bodyType' => 'flat-drop'], false],
            ['box', ['isChangedBodyType' => 'yes', 'bodyType' => 'box'], false],
        ];
    }

    /**
     * @dataProvider dataSubmit
     */
    public function testSubmit(string $initialBodyType, array $formData, bool $expectedValid, ?string $expectedBodyType=null): void
    {
        $vehicle = (new Vehicle())
            ->setBodyType($initialBodyType);

        $data = (new Trip())
            ->setVehicle($vehicle);

        $form = $this->factory->create(ChangedBodyTypeType::class, $data);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isSubmitted());

        $this->assertEquals($expectedValid, $form->isValid());

        if ($expectedValid) {
            $isChangedBodyType = $formData['isChangedBodyType'] === 'yes';

            $this->assertEquals($expectedBodyType, $data->getBodyType());
            $this->assertEquals($isChangedBodyType, $data->getIsChangedBodyType());
        }
    }
}
