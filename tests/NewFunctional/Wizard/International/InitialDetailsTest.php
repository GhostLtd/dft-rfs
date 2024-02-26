<?php

namespace App\Tests\NewFunctional\Wizard\International;

use App\Entity\International\Survey;
use App\Entity\SurveyStateInterface;
use App\Repository\International\SurveyRepository;
use App\Tests\DataFixtures\International\SurveyFixtures;
use App\Tests\NewFunctional\Wizard\AbstractPasscodeWizardTest;
use App\Tests\NewFunctional\Wizard\Action\Context;
use App\Tests\NewFunctional\Wizard\Form\FormTestCase;

class InitialDetailsTest extends AbstractInitialDetailsTest
{
    public function testZeroJourneysAndStillActive(): void
    {
        $this->initialiseTest([SurveyFixtures::class]);

        $this->introAndContactDetails();
        $this->annualJourneyCount(0);
        $this->activityStatus('still-active');
        $this->businessDetails('1-9', 'Testing websites');

        $data = [
            'contactName' => 'My name',
            'contactEmail' => 'email@example.com',
            'contactTelephone' => 'My telephone',
            'annualInternationalJourneyCount' => 0,
            'activityStatus' => 'still-active',
            'numberOfEmployees' => '1-9',
            'businessNature' => 'Testing websites',
        ];

        $this->assertDashboardMatchesData($data);
        $this->assertDatabaseMatchesData($data);
    }

    public function testZeroJourneysAndCeasedTrading(): void
    {
        $this->initialiseTest([SurveyFixtures::class]);

        $this->introAndContactDetails();
        $this->annualJourneyCount(0);
        $this->activityStatus('ceased-trading');

        $data = [
            'contactName' => 'My name',
            'contactEmail' => 'email@example.com',
            'contactTelephone' => 'My telephone',
            'annualInternationalJourneyCount' => 0,
            'activityStatus' => 'ceased-trading',
            'numberOfEmployees' => null,
            'businessNature' => null,
        ];

        $this->assertDashboardMatchesData($data);
        $this->assertDatabaseMatchesData($data);
    }

    public function testZeroJourneysAndOnlyDomesticWork(): void
    {
        $this->initialiseTest([SurveyFixtures::class]);

        $this->introAndContactDetails();
        $this->annualJourneyCount(0);
        $this->activityStatus('only-domestic-work');

        $data = [
            'contactName' => 'My name',
            'contactEmail' => 'email@example.com',
            'contactTelephone' => 'My telephone',
            'annualInternationalJourneyCount' => 0,
            'activityStatus' => 'only-domestic-work',
            'numberOfEmployees' => null,
            'businessNature' => null,
        ];

        $this->assertDashboardMatchesData($data);
        $this->assertDatabaseMatchesData($data);
    }

    public function testManyJourneys(): void
    {
        $this->initialiseTest([SurveyFixtures::class]);

        $this->introAndContactDetails();
        $this->annualJourneyCount(30);
        $this->businessDetails('1-9', 'Testing websites');

        $data = [
            'contactName' => 'My name',
            'contactEmail' => 'email@example.com',
            'contactTelephone' => 'My telephone',
            'annualInternationalJourneyCount' => 30,
            'activityStatus' => 'still-active',
            'numberOfEmployees' => '1-9',
            'businessNature' => 'Testing websites',
        ];

        $this->assertDashboardMatchesData($data);
        $this->assertDatabaseMatchesData($data);
    }

    protected function introAndContactDetails(): void
    {
        $this->formTestAction('/international-survey/initial-details/introduction', 'form_continue', [
            new FormTestCase([]),
        ]);

        $this->formTestAction('/international-survey/initial-details/contact-details', 'contact_details_continue', [
            new FormTestCase([
                'contact_details[contactName]' => 'My name',
                'contact_details[contactTelephone]' => 'My telephone',
                'contact_details[contactEmail]' => 'email@example.com',
            ]),
        ]);
    }

    protected function annualJourneyCount(int $journeyCount): void
    {
        $this->formTestAction('/international-survey/initial-details/number-of-trips', 'number_of_trips_continue', [
            new FormTestCase([
                'number_of_trips[annualInternationalJourneyCount]' => $journeyCount,
            ]),
        ]);
    }

    protected function activityStatus(string $status): void
    {
        $this->formTestAction('/international-survey/initial-details/activity-status', 'activity_status_continue', [
            new FormTestCase([
                'activity_status[activityStatus]' => $status,
            ]),
        ]);
    }

    protected function businessDetails(string $numberOfEmployees, string $businessNature): void
    {
        $this->formTestAction('/international-survey/initial-details/business-details', 'business_details_continue', [
            new FormTestCase([
                'business_details[numberOfEmployees]' => $numberOfEmployees,
                'business_details[businessNature]' => $businessNature,
            ]),
        ]);
    }
}
