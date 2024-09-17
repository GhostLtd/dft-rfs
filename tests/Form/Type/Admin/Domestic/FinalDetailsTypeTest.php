<?php

namespace App\Tests\Form\Type\Admin\Domestic;

use App\Entity\Domestic\Day;
use App\Entity\Domestic\DaySummary;
use App\Entity\Domestic\SurveyResponse;
use App\Entity\Domestic\Vehicle;
use App\Entity\Embeddable\Volume;
use App\Form\Admin\DomesticSurvey\FinalDetailsType;
use App\Tests\Form\Type\Admin\AbstractSurveyTypeTest;
use Symfony\Component\Form\FormError;

class FinalDetailsTypeTest extends AbstractSurveyTypeTest
{
    protected function dataInPossession(): array
    {
        return [
            [['banana' => '123'], false],
            [[], false],

            [['fuel' => ['fuelType' => 'petrol']], false],
            [['fuel' => ['fuelType' => 'petrol', 'fuelDetails_petrol' => ['fuelQuantity_petrol' => 123]]], false],
            [['fuel' => ['fuelType' => 'petrol', 'fuelDetails_petrol' => ['fuelTypeIsHybrid_petrol' => 'true', 'fuelQuantity_petrol' => 123]]], true],

            [['fuel' => ['fuelType' => 'gas']], false],
            [['fuel' => ['fuelType' => 'gas', 'fuelDetails_gas' => ['fuelQuantity_gas' => 123]]], true],

            [['fuel' => ['fuelType' => 'other']], false],
            [['fuel' => ['fuelType' => 'other', 'fuelDetails_other' => ['fuelQuantity_other' => 'free text']]], true],

            [['fuel' => ['fuelType' => 'gas', 'fuelDetails_gas' => ['fuelQuantity_gas' => 123.56]]], true],
            [['fuel' => ['fuelType' => 'gas', 'fuelDetails_gas' => ['fuelQuantity_gas' => 123.567]]], false],
            [['fuel' => ['fuelType' => 'gas', 'fuelDetails_gas' => ['fuelQuantity_gas' => 1234567.56]]], false],
        ];
    }

    /**
     * @dataProvider dataInPossession
     */
    public function testInPossession(array $formData, bool $expectedValid): void
    {
        $data = $this->getSurveyResponse(true);

        $form = $this->factory->create(FinalDetailsType::class, $data);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedValid, $form->isValid());

        if ($expectedValid) {
            $data = $form->getData();

            $this->assertInstanceOf(SurveyResponse::class, $data);
            $vehicle = $data?->getVehicle();
            $type = $vehicle?->getFuelType();

            $this->assertEquals($formData['fuel']['fuelType'] ?? null, $vehicle->getFuelType());
            $this->assertEquals($formData['fuel']["fuelDetails_$type"]["fuelTypeIsHybrid_$type"] ?? null, $vehicle->getIsFuelTypeHybrid());
            if ($type === 'other') {
                $this->assertEquals($formData['fuel']["fuelDetails_$type"]["fuelQuantity_other"] ?? null, $vehicle->getFuelQuantityOther());
            } else {
                $this->assertEquals($formData['fuel']["fuelDetails_$type"]["fuelQuantity_$type"] ?? null, $vehicle->getFuelQuantity());
            }
        }
    }

    protected function dataNotInPossession(): array
    {
        return [
            [[], false],
            [['banana' => '123'], false],

            [['reasonForEmptySurvey' => ''], false],
            [['reasonForEmptySurvey' => 'banana'], false],

            [['reasonForEmptySurvey' => 'not-taxed'], true],
            [['reasonForEmptySurvey' => 'no-work'], true],
            [['reasonForEmptySurvey' => 'repair'], true],
            [['reasonForEmptySurvey' => 'site-work-only'], true],
            [['reasonForEmptySurvey' => 'holiday'], true],
            [['reasonForEmptySurvey' => 'maintenance'], true],
            [['reasonForEmptySurvey' => 'no-driver'], true],

            [['reasonForEmptySurvey' => 'other'], false],
            [['reasonForEmptySurvey' => 'other', 'reasonForEmptySurveyOther' => ''], false],

            [
                [
                    'reasonForEmptySurvey' => 'other',
                    'reasonForEmptySurveyOther' => 'Truck magically turned into a balloon and floated away'
                ], true
            ],
        ];
    }

    /**
     * @dataProvider dataNotInPossession
     */
    public function testNotInPossession(array $formData, bool $expectedValid): void
    {
        $data = $this->getSurveyResponse(false);

        $form = $this->factory->create(FinalDetailsType::class, $data);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedValid, $form->isValid());

        if ($expectedValid) {
            $data = $form->getData();

            $this->assertInstanceOf(SurveyResponse::class, $data);

            $this->assertEquals($formData['reasonForEmptySurvey'] ?? null, $data->getReasonForEmptySurvey());
            $this->assertEquals($formData['reasonForEmptySurveyOther'] ?? null, $data->getReasonForEmptySurveyOther());
        }
    }

    protected function getSurveyResponse(bool $hasJourneys): SurveyResponse
    {
        $response = (new SurveyResponse())
            ->setIsInPossessionOfVehicle(SurveyResponse::IN_POSSESSION_YES);

        $vehicle = (new Vehicle())
            ->setFuelType('petrol')
            ->setIsFuelTypeHybrid(true)
            ->setFuelQuantity(125)
            ->setResponse($response);

        if ($hasJourneys) {
            $day = (new Day())
                ->setHasMoreThanFiveStops(true)
                ->setSummary(new DaySummary());

            $response->addDay($day);
        }

        return $response;
    }
}
