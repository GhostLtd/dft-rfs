<?php

namespace App\Tests\Form\Type\Domestic\ClosingDetails;

use App\Entity\Domestic\SurveyResponse;
use App\Entity\Domestic\Vehicle;
use App\Entity\Volume;
use App\Form\DomesticSurvey\ClosingDetails\ReasonEmptySurveyType;
use App\Form\DomesticSurvey\ClosingDetails\VehicleFuelType;
use App\Tests\Form\Type\AbstractTypeTest;

class VehicleFuelTypeTest extends AbstractTypeTest
{
    protected function dataSubmit(): array
    {
        return [
            [[], true], // We're allowed to leave this form blank
            [['banana' => '123'], false],

            [['fuelQuantity' => ['value' => '', 'unit' => '']], true],

            [
                ['fuelQuantity' => ['value' => '27', 'unit' => 'litres']],
                true,
                '27.00',
                'litres',
            ],
            [
                ['fuelQuantity' => ['value' => '27', 'unit' => 'gallons']],
                true,
                '27.00',
                'gallons',
            ],
            [
                ['fuelQuantity' => ['value' => '27', 'unit' => 'bananas']],
                false,
                '27.00',
                null,
            ],

            // Number extremities
            [
                ['fuelQuantity' => ['value' => '0', 'unit' => 'litres']],
                true,
                '0.00',
                'litres',
            ],
            [
                ['fuelQuantity' => ['value' => '27.53', 'unit' => 'litres']],
                true,
                '27.53',
                'litres',
            ],
            [
                ['fuelQuantity' => ['value' => '999999.99', 'unit' => 'litres']],
                true,
                '999999.99',
                'litres',
            ],
            [
                ['fuelQuantity' => ['value' => '9999999.99', 'unit' => 'litres']],
                false, // Field has precision 8, scale 2
                '9999999.99',
                'litres',
            ],
            [
                ['fuelQuantity' => ['value' => '27.531', 'unit' => 'litres']],
                false, // Field has scale 2
                null,
                'litres',
            ],
            [
                ['fuelQuantity' => ['value' => '-1.2', 'unit' => 'litres']],
                false,
                null,
                'litres',
            ],
            [
                ['fuelQuantity' => ['value' => '2e5', 'unit' => 'litres']],
                false,
                null,
                'litres',
            ],
        ];
    }

    /**
     * @dataProvider dataSubmit
     */
    public function testSubmit(array $formData, bool $expectedValid, ?string $expectedValue=null, ?string $expectedUnit=null): void
    {
        $data = new SurveyResponse();
        $vehicle = (new Vehicle())->setFuelQuantity(new Volume());
        $data->setVehicle($vehicle);

        $form = $this->factory->create(VehicleFuelType::class, $data);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isSubmitted());

        $this->assertEquals($expectedValid, $form->isValid());

        if ($expectedValid) {
            $fuelQuantity = $data->getVehicle()?->getFuelQuantity();
            $this->assertEquals($expectedValue, $fuelQuantity?->getValue());
            $this->assertEquals($expectedUnit, $fuelQuantity?->getUnit());
        }
    }
}
