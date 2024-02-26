<?php

namespace App\Tests\Form\Type\International\InitialDetails;

use App\Entity\International\SurveyResponse;
use App\Form\InternationalSurvey\InitialDetails\NumberOfTripsType;
use App\Tests\Form\Type\AbstractTypeTest;

class NumberOfTripsTypeTest extends AbstractTypeTest
{
    protected function dataSubmit(): array
    {
        return [
            [[], false],
            [['banana' => '123'], false],

            [['annualInternationalJourneyCount' => 'banana'], false],
            [['annualInternationalJourneyCount' => ''], false],

            [['annualInternationalJourneyCount' => '0'], true, SurveyResponse::ACTIVITY_STATUS_CEASED_TRADING, SurveyResponse::ACTIVITY_STATUS_CEASED_TRADING],
            [['annualInternationalJourneyCount' => '100'], true, SurveyResponse::ACTIVITY_STATUS_CEASED_TRADING, SurveyResponse::ACTIVITY_STATUS_STILL_ACTIVE],
            [['annualInternationalJourneyCount' => '0'], true, SurveyResponse::ACTIVITY_STATUS_ONLY_DOMESTIC_WORK, SurveyResponse::ACTIVITY_STATUS_ONLY_DOMESTIC_WORK],
            [['annualInternationalJourneyCount' => '100'], true, SurveyResponse::ACTIVITY_STATUS_ONLY_DOMESTIC_WORK, SurveyResponse::ACTIVITY_STATUS_STILL_ACTIVE],
            [['annualInternationalJourneyCount' => '0'], true, SurveyResponse::ACTIVITY_STATUS_STILL_ACTIVE, SurveyResponse::ACTIVITY_STATUS_STILL_ACTIVE],
            [['annualInternationalJourneyCount' => '100'], true, SurveyResponse::ACTIVITY_STATUS_STILL_ACTIVE, SurveyResponse::ACTIVITY_STATUS_STILL_ACTIVE],        ];
    }

    /**
     * @dataProvider dataSubmit
     */
    public function testSubmit(array $formData, bool $expectedValid, ?string $initialActivityStatus=null, ?string $expectedActivityStatus=null): void
    {
        $data = (new SurveyResponse())
            ->setActivityStatus($initialActivityStatus);

        $form = $this->factory->create(NumberOfTripsType::class, $data);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isSubmitted());

        $this->assertEquals($expectedValid, $form->isValid());

        if ($expectedValid) {
            $this->assertEquals($formData['annualInternationalJourneyCount'], $data->getAnnualInternationalJourneyCount());
            $this->assertEquals($expectedActivityStatus, $data->getActivityStatus());
        }
    }
}
