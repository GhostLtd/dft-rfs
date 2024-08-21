<?php

namespace App\Tests\Form\Type\Admin\Domestic;

use App\Entity\Domestic\Day;
use App\Entity\Domestic\DaySummary;
use App\Entity\Domestic\SurveyResponse;
use App\Entity\Domestic\Vehicle;
use App\Entity\Volume;
use App\Form\Admin\DomesticSurvey\FinalDetailsType;
use App\Tests\Form\Type\Admin\AbstractSurveyTypeTest;

class FinalDetailsTypeTest extends AbstractSurveyTypeTest
{
    protected function dataInPossession(): array
    {
        return [
            [['banana' => '123'], false],

            [['fuelQuantity' => ['unit' => 'litres', 'value' => 120]], true],
            [['fuelQuantity' => ['unit' => 'gallons', 'value' => 35.77]], true],

            [['fuelQuantity' => ['unit' => 'bananas', 'value' => 35.5]], false],
            [['fuelQuantity' => ['unit' => 'gallons', 'value' => 123456.12]], true],

            // Too many decimal places and/or digits
            [['fuelQuantity' => ['unit' => 'gallons', 'value' => 35.777]], false],
            [['fuelQuantity' => ['unit' => 'gallons', 'value' => 1234567.12]], false],

            [[], true],

            // Strangely with this form, you can set the units without the values
            [['fuelQuantity' => ['unit' => 'litres']], true],
            [['fuelQuantity' => ['unit' => 'gallons']], true],

            // But not the value without the unit
            [['fuelQuantity' => ['value' => 34]], false],
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
            $fuelQuantity = $data?->getVehicle()?->getFuelQuantity();

            $this->assertEquals($formData['fuelQuantity']['unit'] ?? null, $fuelQuantity?->getUnit());
            $this->assertEquals($formData['fuelQuantity']['value'] ?? null, $fuelQuantity?->getValue());
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
        $volume = (new Volume())
            ->setUnit(Volume::UNIT_LITRES)
            ->setValue(125);

        $vehicle = (new Vehicle())
            ->setFuelQuantity($volume);

        $response = (new SurveyResponse())
            ->setIsInPossessionOfVehicle(SurveyResponse::IN_POSSESSION_YES)
            ->setVehicle($vehicle);

        if ($hasJourneys) {
            $day = (new Day())
                ->setHasMoreThanFiveStops(true)
                ->setSummary(new DaySummary());

            $response->addDay($day);
        }

        return $response;
    }
}
