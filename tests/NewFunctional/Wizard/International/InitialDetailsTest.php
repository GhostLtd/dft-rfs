<?php

namespace App\Tests\NewFunctional\Wizard\International;

use App\Entity\International\Survey;
use App\Entity\SurveyStateInterface;
use App\Repository\International\SurveyRepository;
use App\Tests\DataFixtures\International\SurveyFixtures;
use App\Tests\NewFunctional\Wizard\AbstractPasscodeWizardTestCase;
use App\Tests\NewFunctional\Wizard\Action\Context;
use App\Tests\NewFunctional\Wizard\Form\FormTestCase;

/**
 * Tests the IRHS initial details wizard flow.
 *
 * Workflow order:
 * 1. contact-details
 * 2. activity-status
 * 3. (if STILL_ACTIVE) business-details
 * 4. (if STILL_ACTIVE) number-of-trips
 */
class InitialDetailsTest extends AbstractInitialDetailsTestCase
{
    /**
     * Tests the flow when user selects "still-active" with zero HGV journeys.
     * Zero HGV journeys no longer ends the survey: the firm reaches the dashboard and
     * may still add vehicles or submit an empty survey.
     */
    public function testZeroJourneysAndStillActive(): void
    {
        $this->initialiseTest([SurveyFixtures::class]);

        $this->introAndContactDetails();
        $this->activityStatus('still-active');
        $this->businessDetails('1-9', 'Testing websites');
        $this->annualJourneyCount(0, 0);

        $data = [
            'contactName' => 'My name',
            'contactEmail' => 'email@example.com',
            'contactTelephone' => 'My telephone',
            'annualInternationalHGVJourneyCount' => 0,
            'annualInternationalLGVJourneyCount' => 0,
            'activityStatus' => 'still-active',
            'numberOfEmployees' => '1-9',
            'businessNature' => 'Testing websites',
        ];

        $this->assertDashboardMatchesData($data);
        $this->assertDatabaseMatchesData($data);
    }

    /**
     * Tests the flow when user selects "ceased-trading".
     * Survey ends immediately after activity-status - no business details or trips needed.
     */
    public function testZeroJourneysAndCeasedTrading(): void
    {
        $this->initialiseTest([SurveyFixtures::class]);

        $this->introAndContactDetails();
        $this->activityStatus('ceased-trading');
        // No business details or journey count - survey ends here

        $data = [
            'contactName' => 'My name',
            'contactEmail' => 'email@example.com',
            'contactTelephone' => 'My telephone',
            'annualInternationalHGVJourneyCount' => null,
            'annualInternationalLGVJourneyCount' => null,
            'activityStatus' => 'ceased-trading',
            'numberOfEmployees' => null,
            'businessNature' => null,
        ];

        $this->assertDashboardMatchesData($data);
        $this->assertDatabaseMatchesData($data);
    }

    /**
     * Tests the flow when user selects "only-domestic-work".
     * Survey ends immediately after activity-status - no business details or trips needed.
     */
    public function testZeroJourneysAndOnlyDomesticWork(): void
    {
        $this->initialiseTest([SurveyFixtures::class]);

        $this->introAndContactDetails();
        $this->activityStatus('only-domestic-work');
        // No business details or journey count - survey ends here

        $data = [
            'contactName' => 'My name',
            'contactEmail' => 'email@example.com',
            'contactTelephone' => 'My telephone',
            'annualInternationalHGVJourneyCount' => null,
            'annualInternationalLGVJourneyCount' => null,
            'activityStatus' => 'only-domestic-work',
            'numberOfEmployees' => null,
            'businessNature' => null,
        ];

        $this->assertDashboardMatchesData($data);
        $this->assertDatabaseMatchesData($data);
    }

    /**
     * Tests the full flow with positive HGV journeys.
     * This is the typical case where vehicle/trip data will be collected.
     */
    public function testManyJourneys(): void
    {
        $this->initialiseTest([SurveyFixtures::class]);

        $this->introAndContactDetails();
        $this->activityStatus('still-active');
        $this->businessDetails('1-9', 'Testing websites');
        $this->annualJourneyCount(30, 10);

        $data = [
            'contactName' => 'My name',
            'contactEmail' => 'email@example.com',
            'contactTelephone' => 'My telephone',
            'annualInternationalHGVJourneyCount' => 30,
            'annualInternationalLGVJourneyCount' => 10,
            'activityStatus' => 'still-active',
            'numberOfEmployees' => '1-9',
            'businessNature' => 'Testing websites',
        ];

        $this->assertDashboardMatchesData($data);
        $this->assertDatabaseMatchesData($data);
    }

    /**
     * Tests the LGV-only scenario (0 HGV, positive LGV).
     * The survey only collects HGV vehicle data, but a still-active firm is no longer
     * ended here: it reaches the dashboard and may add vehicles or submit empty.
     */
    public function testLgvOnlyJourneys(): void
    {
        $this->initialiseTest([SurveyFixtures::class]);

        $this->introAndContactDetails();
        $this->activityStatus('still-active');
        $this->businessDetails('1-9', 'Van haulage only');
        $this->annualJourneyCount(0, 50);

        $data = [
            'contactName' => 'My name',
            'contactEmail' => 'email@example.com',
            'contactTelephone' => 'My telephone',
            'annualInternationalHGVJourneyCount' => 0,
            'annualInternationalLGVJourneyCount' => 50,
            'activityStatus' => 'still-active',
            'numberOfEmployees' => '1-9',
            'businessNature' => 'Van haulage only',
        ];

        $this->assertDashboardMatchesData($data);
        $this->assertDatabaseMatchesData($data);
    }

    protected function introAndContactDetails(): void
    {
        $this->formTestAction('/international-survey/initial-details/introduction', 'form_button_group_continue', [
            new FormTestCase([]),
        ]);

        $this->formTestAction('/international-survey/initial-details/contact-details', 'contact_details_button_group_continue', [
            new FormTestCase([
                'contact_details[contactName]' => 'My name',
                'contact_details[contactTelephone]' => 'My telephone',
                'contact_details[contactEmail]' => 'email@example.com',
            ]),
        ]);
    }

    protected function activityStatus(string $status): void
    {
        $this->formTestAction('/international-survey/initial-details/activity-status', 'activity_status_button_group_continue', [
            new FormTestCase([
                'activity_status[activityStatus]' => $status,
            ]),
        ]);
    }

    protected function businessDetails(string $numberOfEmployees, string $businessNature): void
    {
        $this->formTestAction('/international-survey/initial-details/business-details', 'business_details_button_group_continue', [
            new FormTestCase([
                'business_details[numberOfEmployees]' => $numberOfEmployees,
                'business_details[businessNature]' => $businessNature,
            ]),
        ]);
    }

    protected function annualJourneyCount(int $hgvJourneyCount, int $lgvJourneyCount): void
    {
        $this->formTestAction('/international-survey/initial-details/number-of-trips', 'number_of_trips_button_group_continue', [
            new FormTestCase([
                'number_of_trips[annualInternationalHGVJourneyCount]' => $hgvJourneyCount,
                'number_of_trips[annualInternationalLGVJourneyCount]' => $lgvJourneyCount,
            ]),
        ]);
    }
}
