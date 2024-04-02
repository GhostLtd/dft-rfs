<?php

namespace App\Tests\Form\Type\Domestic\DayStop;

use App\Entity\Domestic\DayStop;
use App\Form\DomesticSurvey\DayStop\DistanceTravelledType;
use App\Tests\Form\Type\AbstractTypeTest;

class DistanceTravelledTypeTest extends AbstractTypeTest
{
    protected function dataSubmit(): array
    {
        return [
            [[], false],
            [['banana' => '123'], false],

            [['distanceTravelled' => ['value' => '', 'unit' => '']], false],

            [
                ['distanceTravelled' => ['value' => '27', 'unit' => 'miles']],
                true,
                '27.0',
                'miles',
            ],
            [
                ['distanceTravelled' => ['value' => '27', 'unit' => 'kilometres']],
                true,
                '27.0',
                'kilometres',
            ],
            [
                ['distanceTravelled' => ['value' => '27', 'unit' => 'bananas']],
                false,
            ],

            // Number extremities
            [
                ['distanceTravelled' => ['value' => '0', 'unit' => 'miles']],
                true,
                '0.0',
                'miles',
            ],
            [
                ['distanceTravelled' => ['value' => '27.5', 'unit' => 'miles']],
                true,
                '27.5',
                'miles',
            ],
            [
                ['distanceTravelled' => ['value' => '999999999.9', 'unit' => 'miles']],
                true,
                '999999999.9',
                'miles',
            ],
            [
                ['distanceTravelled' => ['value' => '9999999999.9', 'unit' => 'miles']],
                false, // Field has precision 10, scale 1
            ],
            [
                ['distanceTravelled' => ['value' => '27.53', 'unit' => 'miles']],
                false, // Field has scale 1
            ],
            [
                ['distanceTravelled' => ['value' => '-1.2', 'unit' => 'miles']],
                false,
            ],
            [
                ['distanceTravelled' => ['value' => '2e5', 'unit' => 'miles']],
                false,
            ],
        ];
    }

    /**
     * @dataProvider dataSubmit
     */
    public function testSubmit(array $formData, bool $expectedValid, ?string $expectedValue=null, ?string $expectedUnit=null): void
    {
        $data = new DayStop();

        $form = $this->factory->create(DistanceTravelledType::class, $data);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isSubmitted());

        $this->assertEquals($expectedValid, $form->isValid());

        if ($expectedValid) {
            $distanceTravelled = $data->getDistanceTravelled();
            $this->assertEquals($expectedValue, $distanceTravelled?->getValue());
            $this->assertEquals($expectedUnit, $distanceTravelled?->getUnit());
        }
    }
}
