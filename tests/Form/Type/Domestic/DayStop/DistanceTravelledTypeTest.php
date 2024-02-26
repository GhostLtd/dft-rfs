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
                '27.00',
                'miles',
            ],
            [
                ['distanceTravelled' => ['value' => '27', 'unit' => 'kilometres']],
                true,
                '27.00',
                'kilometres',
            ],
            [
                ['distanceTravelled' => ['value' => '27', 'unit' => 'bananas']],
                false,
                '27.00',
                null,
            ],

            // Number extremities
            [
                ['distanceTravelled' => ['value' => '0', 'unit' => 'miles']],
                true,
                '0.00',
                'miles',
            ],
            [
                ['distanceTravelled' => ['value' => '27.53', 'unit' => 'miles']],
                true,
                '27.53',
                'miles',
            ],
            [
                ['distanceTravelled' => ['value' => '99999999.99', 'unit' => 'miles']],
                true,
                '99999999.99',
                'miles',
            ],
            // TODO: Either column declaration or transformer usage needs changing
            //       Currently column is (10,1) whereas transformer is (8,2)
            [
                ['distanceTravelled' => ['value' => '999999999.99', 'unit' => 'miles']],
                false, // Field has precision 8, scale 2
                '999999999.99',
                'miles',
            ],
            [
                ['distanceTravelled' => ['value' => '27.531', 'unit' => 'miles']],
                false, // Field has scale 2
                null,
                'miles',
            ],
            [
                ['distanceTravelled' => ['value' => '-1.2', 'unit' => 'miles']],
                false,
                null,
                'miles',
            ],
            [
                ['distanceTravelled' => ['value' => '2e5', 'unit' => 'miles']],
                false,
                null,
                'miles',
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
