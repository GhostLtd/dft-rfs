<?php

namespace App\Tests\NewFunctional\Wizard\International;

use App\Tests\NewFunctional\Wizard\Form\FormTestCase;

class InitialDetailsChangeTest extends AbstractInitialDetailsTest
{
    public function testChangeCorrespondenceDetails(): void
    {
        $this->performChangeTest(0, function(array &$expectedData) {
            $this->formTestAction('/international-survey/initial-details/change-contact-details', 'contact_details_buttons_continue', [
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

    /**
     * Tests changing only the number of trips.
     * Index 6 = "Estimated HGV trips" change link.
     * Since number-of-trips is the last step in the wizard, submitting returns to summary.
     */
    public function testChangeNumberOfTrips(): void
    {
        $this->performChangeTest(6, function(array &$expectedData) {
            $this->formTestAction('/international-survey/initial-details/number-of-trips', 'number_of_trips_buttons_continue', [
                new FormTestCase([
                    'number_of_trips[annualInternationalHGVJourneyCount]' => '75',
                    'number_of_trips[annualInternationalLGVJourneyCount]' => '25',
                ]),
            ]);

            $expectedData['annualInternationalHGVJourneyCount'] = 75;
            $expectedData['annualInternationalLGVJourneyCount'] = 25;
        });

        $this->getElementByTextContains('a', 'Back to dashboard');
    }

    /**
     * Tests changing business details.
     * Index 4 = "Nature" change link (5 = "Number of employees").
     * Since business-details comes before number-of-trips in the workflow,
     * after submitting business-details we must also submit number-of-trips.
     */
    public function testChangeBusinessDetails(): void
    {
        $this->performChangeTest(4, function(array &$expectedData) {
            $this->formTestAction('/international-survey/initial-details/business-details', 'business_details_buttons_continue', [
                new FormTestCase([
                    'business_details[numberOfEmployees]' => '1-9',
                    'business_details[businessNature]' => 'Owls',
                ]),
            ]);

            // After business-details, wizard continues to number-of-trips
            $this->formTestAction('/international-survey/initial-details/number-of-trips', 'number_of_trips_buttons_continue', [
                new FormTestCase([
                    'number_of_trips[annualInternationalHGVJourneyCount]' => '150',
                    'number_of_trips[annualInternationalLGVJourneyCount]' => '0',
                ]),
            ]);

            $expectedData['numberOfEmployees'] = '1-9';
            $expectedData['businessNature'] = 'Owls';
        });

        $this->getElementByTextContains('a', 'Back to dashboard');
    }

    /**
     * Tests changing a still-active survey to not active.
     * Index 6 = "Estimated HGV trips" change link.
     * After changing trips to 0, we return to summary, then click Change for activity-status (index 3).
     */
    public function testMakeNotActive(): void
    {
        $this->performChangeTest(6, function(array &$expectedData) {
            // First, change trips to 0
            $this->formTestAction('/international-survey/initial-details/number-of-trips', 'number_of_trips_buttons_continue', [
                new FormTestCase([
                    'number_of_trips[annualInternationalHGVJourneyCount]' => '0',
                    'number_of_trips[annualInternationalLGVJourneyCount]' => '0',
                ]),
            ]);

            // After changing trips, we're back at the summary page
            // Now click the Change link for activity status (index 3) to change activity
            $this->pathTestAction('/international-survey/correspondence-and-business-details');
            $this->clickLinkContaining('Change', 3);

            $this->formTestAction('/international-survey/initial-details/activity-status', 'activity_status_buttons_continue', [
                new FormTestCase([
                    'activity_status[activityStatus]' => 'only-domestic-work',
                ]),
            ]);

            $expectedData['annualInternationalHGVJourneyCount'] = 0;
            $expectedData['annualInternationalLGVJourneyCount'] = 0;
            $expectedData['activityStatus'] = 'only-domestic-work';
            $expectedData['businessNature'] = null;
            $expectedData['numberOfEmployees'] = null;
        });

        $this->getElementByTextContains('button', 'Submit survey');
    }
}
