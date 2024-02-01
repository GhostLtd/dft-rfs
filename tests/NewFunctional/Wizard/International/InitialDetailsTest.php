<?php

namespace App\Tests\NewFunctional\Wizard\International;

use App\Entity\International\Survey;
use App\Entity\SurveyStateInterface;
use App\Repository\International\SurveyRepository;
use App\Tests\DataFixtures\International\SurveyFixtures;
use App\Tests\NewFunctional\Wizard\AbstractPasscodeWizardTest;
use App\Tests\NewFunctional\Wizard\Action\Context;
use App\Tests\NewFunctional\Wizard\Form\FormTestCase;

class InitialDetailsTest extends AbstractPasscodeWizardTest
{
    public function testZeroJourneysAndStillActive(): void
    {
        $this->initialiseTest([SurveyFixtures::class]);

        $this->introAndContactDetails();
        $this->annualJourneyCount(0);
        $this->activityStatus('still-active');
        $this->businessDetails('1-9', 'Testing websites');
        $this->assertAtDashboard();
        $this->assertDatabaseCorrect(
            'My name',
            'email@example.com',
            'My telephone',
            0,
            'still-active',
            '1-9',
            'Testing websites',
        );
    }

    public function testZeroJourneysAndCeasedTrading(): void
    {
        $this->initialiseTest([SurveyFixtures::class]);

        $this->introAndContactDetails();
        $this->annualJourneyCount(0);
        $this->activityStatus('ceased-trading');
        $this->assertAtDashboard();
        $this->assertDatabaseCorrect(
            'My name',
            'email@example.com',
            'My telephone',
            0,
            'ceased-trading',
            null,
            null,
        );
    }

    public function testZeroJourneysAndOnlyDomesticWork(): void
    {
        $this->initialiseTest([SurveyFixtures::class]);

        $this->introAndContactDetails();
        $this->annualJourneyCount(0);
        $this->activityStatus('only-domestic-work');
        $this->assertAtDashboard();
        $this->assertDatabaseCorrect(
            'My name',
            'email@example.com',
            'My telephone',
            0,
            'only-domestic-work',
            null,
            null,
        );
    }

    public function testManyJourneys(): void
    {
        $this->initialiseTest([SurveyFixtures::class]);

        $this->introAndContactDetails();
        $this->annualJourneyCount(30);
        $this->businessDetails('1-9', 'Testing websites');
        $this->assertAtDashboard();
        $this->assertDatabaseCorrect(
            'My name',
            'email@example.com',
            'My telephone',
            30,
            'still-active',
            '1-9',
            'Testing websites',
        );
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

    protected function assertAtDashboard(): void
    {
        $this->pathTestAction('/international-survey/correspondence-and-business-details');
    }

    protected function assertDatabaseCorrect(
        string $name,
        string $email,
        string $telephone,
        int $annualJourneyCount,
        string $activityStatus,
        ?string $numberOfEmployees,
        ?string $businessNature,
    ): void
    {
        $this->callbackTestAction(function (Context $context) use ($name, $email, $telephone, $annualJourneyCount, $activityStatus, $numberOfEmployees, $businessNature) {
            $entityManager = $context->getEntityManager();

            /** @var SurveyRepository $repo */
            $repo = $entityManager->getRepository(Survey::class);
            $entityManager->clear();
            $surveys = $repo->findAll();

            $test = $context->getTestCase();
            $test->assertCount(1, $surveys, 'Expected a single surveyResponse to be in the database');

            $survey = $surveys[0];
            $test->assertEquals(SurveyStateInterface::STATE_IN_PROGRESS, $survey->getState());

            $response = $survey->getResponse();
            $test->assertEquals($activityStatus, $response->getActivityStatus());
            $test->assertEquals($name, $response->getContactName());
            $test->assertEquals($email, $response->getContactEmail());
            $test->assertEquals($telephone, $response->getContactTelephone());
            $test->assertEquals($numberOfEmployees, $response->getNumberOfEmployees());
            $test->assertEquals($annualJourneyCount, $response->getAnnualInternationalJourneyCount());
            $test->assertEquals($businessNature, $response->getBusinessNature());
        });
    }
}