<?php

namespace App\Tests\Form\Type\International\InitialDetails;

use App\Entity\International\SurveyResponse;
use App\Form\InternationalSurvey\InitialDetails\NumberOfTripsType;
use App\Tests\Form\Type\AbstractTypeTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests the NumberOfTripsType form.
 *
 * Note: This form is only reached when activity status is STILL_ACTIVE,
 * so we don't test activity status transitions here.
 */
class NumberOfTripsTypeTest extends AbstractTypeTestCase
{
    public static function dataSubmit(): array
    {
        return [
            'empty submission' => [[], false],
            'invalid field name' => [['banana' => '123'], false],
            'invalid HGV value (text)' => [['annualInternationalHGVJourneyCount' => 'banana'], false],
            'invalid HGV value (empty)' => [['annualInternationalHGVJourneyCount' => ''], false],
            'zero HGV and LGV journeys' => [['annualInternationalHGVJourneyCount' => '0', 'annualInternationalLGVJourneyCount' => '0'], true],
            'positive HGV, zero LGV' => [['annualInternationalHGVJourneyCount' => '100', 'annualInternationalLGVJourneyCount' => '0'], true],
            'zero HGV, positive LGV' => [['annualInternationalHGVJourneyCount' => '0', 'annualInternationalLGVJourneyCount' => '50'], true],
            'positive HGV and LGV' => [['annualInternationalHGVJourneyCount' => '100', 'annualInternationalLGVJourneyCount' => '50'], true],
        ];
    }

    #[DataProvider('dataSubmit')]
    public function testSubmit(array $formData, bool $expectedValid): void
    {
        // Form is only reached when activity status is STILL_ACTIVE
        $data = (new SurveyResponse())
            ->setActivityStatus(SurveyResponse::ACTIVITY_STATUS_STILL_ACTIVE);

        $form = $this->factory->create(NumberOfTripsType::class, $data);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isSubmitted());

        $this->assertEquals($expectedValid, $form->isValid());

        if ($expectedValid) {
            $this->assertEquals($formData['annualInternationalHGVJourneyCount'], $data->getAnnualInternationalHGVJourneyCount());
            $this->assertEquals($formData['annualInternationalLGVJourneyCount'], $data->getAnnualInternationalLGVJourneyCount());
            // Activity status should remain STILL_ACTIVE (form doesn't change it)
            $this->assertEquals(SurveyResponse::ACTIVITY_STATUS_STILL_ACTIVE, $data->getActivityStatus());
        }
    }
}
