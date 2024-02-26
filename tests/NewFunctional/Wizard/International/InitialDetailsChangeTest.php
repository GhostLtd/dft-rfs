<?php

namespace App\Tests\NewFunctional\Wizard\International;

use App\Tests\NewFunctional\Wizard\Form\FormTestCase;

class InitialDetailsChangeTest extends AbstractInitialDetailsTest
{
    public function testChangeCorrespondenceDetails(): void
    {
        $this->performChangeTest(0, function(array &$expectedData) {
            $this->formTestAction('/international-survey/initial-details/change-contact-details', 'contact_details_continue', [
                new FormTestCase([
                    'contact_details[contactName]' => 'Mark',
                    'contact_details[contactTelephone]' => '',
                    'contact_details[contactEmail]' => 'mark@example.com',
                ]),
            ]);

            $expectedData['contactName'] = 'Mark';
            $expectedData['contactTelephone'] = null;
            $expectedData['contactEmail'] = 'mark@example.com';
        });

        $this->getElementByTextContains('a', 'Back to dashboard');
    }

    public function testChangeNumberOfTrips(): void
    {
        $this->performChangeTest(3, function(array &$expectedData) {
            $this->formTestAction('/international-survey/initial-details/number-of-trips', 'number_of_trips_continue', [
                new FormTestCase([
                    'number_of_trips[annualInternationalJourneyCount]' => '75',
                ]),
            ]);

            $this->formTestAction('/international-survey/initial-details/business-details', 'business_details_continue', [
                new FormTestCase([
                    'business_details[numberOfEmployees]' => '50-249',
                    'business_details[businessNature]' => 'Giggles',
                ]),
            ]);

            $expectedData['annualInternationalJourneyCount'] = 75;
            $expectedData['numberOfEmployees'] = '50-249';
            $expectedData['businessNature'] = 'Giggles';
        });

        $this->getElementByTextContains('a', 'Back to dashboard');
    }

    public function testChangeBusinessDetails(): void
    {
        $this->performChangeTest(4, function(array &$expectedData) {
            $this->formTestAction('/international-survey/initial-details/business-details', 'business_details_continue', [
                new FormTestCase([
                    'business_details[numberOfEmployees]' => '1-9',
                    'business_details[businessNature]' => 'Owls',
                ]),
            ]);

            $expectedData['numberOfEmployees'] = '1-9';
            $expectedData['businessNature'] = 'Owls';
        });

        $this->getElementByTextContains('a', 'Back to dashboard');
    }

    public function testMakeNotActive(): void
    {
        $this->performChangeTest(3, function(array &$expectedData) {
            $this->formTestAction('/international-survey/initial-details/number-of-trips', 'number_of_trips_continue', [
                new FormTestCase([
                    'number_of_trips[annualInternationalJourneyCount]' => '0',
                ]),
            ]);

            $this->formTestAction('/international-survey/initial-details/activity-status', 'activity_status_continue', [
                new FormTestCase([
                    'activity_status[activityStatus]' => 'only-domestic-work',
                ]),
            ]);

            $expectedData['annualInternationalJourneyCount'] = 0;
            $expectedData['activityStatus'] = 'only-domestic-work';
            $expectedData['businessNature'] = null;
            $expectedData['numberOfEmployees'] = null;
        });

        $this->getElementByTextContains('button', 'Submit survey');
    }
}
