<?php

namespace App\Tests\Form\Type\Domestic\ClosingDetails;

use App\Entity\Domestic\SurveyResponse;
use App\Entity\Domestic\Vehicle;
use App\Entity\Embeddable\Volume;
use App\Form\DomesticSurvey\ClosingDetails\VehicleFuelType;
use App\Tests\Form\Type\AbstractTypeTest;
use Symfony\Component\Form\FormError;

class VehicleFuelTypeTest extends AbstractTypeTest
{
    protected function dataSubmit(): array
    {
        return [
            [[], false], // We're allowed to leave this form blank
            [['banana' => '123'], false],

            [['fuelType' => ''], false],

            [
                ['fuelType' => 'gas', 'fuelDetails_gas' => ['fuelQuantity_gas' => 123]],
                true,
                'gas',
                '123.00',
            ],
            [
                ['fuelType' => 'petrol', 'fuelDetails_petrol' => ['fuelTypeIsHybrid_petrol' => 'true', 'fuelQuantity_petrol' => 123]],
                true,
                'petrol',
                '123.00',
                true,
            ],
            [
                ['fuelType' => 'bananas'],
                false,
            ],

            // Number extremities
            [
                ['fuelType' => 'gas', 'fuelDetails_gas' => ['fuelQuantity_gas' => '0']],
                true,
                'gas',
                '0.00',
            ],
            [
                ['fuelType' => 'gas', 'fuelDetails_gas' => ['fuelQuantity_gas' => '27.53']],
                true,
                'gas',
                '27.53',
            ],
            [
                ['fuelType' => 'gas', 'fuelDetails_gas' => ['fuelQuantity_gas' => '999999.99']],
                true,
                'gas',
                '999999.99',
            ],
            [
                ['fuelType' => 'gas', 'fuelDetails_gas' => ['fuelQuantity_gas' => '9999999.99']],
                false, // Field has precision 8, scale 2
                'gas',
                '9999999.99',
            ],
            [
                ['fuelType' => 'gas', 'fuelDetails_gas' => ['fuelQuantity_gas' => '27.531']],
                false, // Field has scale 2
                'gas',
                null,
            ],
            [
                ['fuelType' => 'gas', 'fuelDetails_gas' => ['fuelQuantity_gas' => '-1.2']],
                false,
                'gas',
                null,
            ],
            [
                ['fuelType' => 'gas', 'fuelDetails_gas' => ['fuelQuantity_gas' => '2e5']],
                false,
                'gas',
                null,
            ],
        ];
    }

    /**
     * @dataProvider dataSubmit
     */
    public function testSubmit(array $formData, bool $expectedValid, ?string $expectedType=null, ?string $expectedQuantity=null, ?bool $expectedIsHybrid=null): void
    {
        $data = new SurveyResponse();
        (new Vehicle())
            ->setResponse($data)
            ->setFuelType(null)
        ;

        $form = $this->factory->create(VehicleFuelType::class, $data);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isSubmitted());

//        dump(array_map(fn(FormError $e) => $e->getMessage(), iterator_to_array($form->getErrors(true, true))));
        $this->assertEquals($expectedValid, $form->isValid());

        if ($expectedValid) {
            $vehicle = $data->getVehicle();
            $this->assertEquals($expectedType, $vehicle?->getFuelType());
            if ($vehicle->getFuelType() === 'other') {
                $this->assertEquals($expectedQuantity, $vehicle?->getFuelQuantityOther());
                $this->assertNull($vehicle?->getFuelQuantity());
            } else {
                $this->assertEquals($expectedQuantity, $vehicle?->getFuelQuantity());
                $this->assertNull($vehicle?->getFuelQuantityOther());
            }
            $this->assertEquals($expectedIsHybrid, $vehicle?->getIsFuelTypeHybrid());
        }
    }
}
