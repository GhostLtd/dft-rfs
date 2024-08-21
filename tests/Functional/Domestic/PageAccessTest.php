<?php

namespace App\Tests\Functional\Domestic;

use App\Tests\DataFixtures\Domestic\ResponseExemptVehicleTypeFixtures;
use App\Tests\DataFixtures\Domestic\ResponseFixtures;
use App\Tests\DataFixtures\Domestic\ResponseScrappedButInProgressFixtures;
use App\Tests\DataFixtures\Domestic\ResponseSoldButInProgressFixtures;
use App\Tests\DataFixtures\Domestic\SurveyClosedFixtures;
use App\Tests\DataFixtures\Domestic\SurveyFixtures;
use App\Tests\DataFixtures\Domestic\ResponseOnHireButInProgress;
use App\Tests\DataFixtures\Domestic\VehicleFixtures;
use App\Tests\Functional\AbstractWizardTest;

class PageAccessTest extends AbstractWizardTest
{
    public function dataNewSurveyPageAccess(): array
    {
        return [
            [[SurveyFixtures::class], '/domestic-survey', 302],
            [[SurveyFixtures::class], '/domestic-survey/contact-and-business-details', 302, '/domestic-survey'],
            [[SurveyFixtures::class], '/domestic-survey/closing-details', 403],
            [[SurveyFixtures::class], '/domestic-survey/close-exempt', 403],
            [[SurveyFixtures::class], '/domestic-survey/close-not-in-possession', 403],
            [[SurveyFixtures::class], '/domestic-survey/completed', 302],

            [[SurveyClosedFixtures::class], '/domestic-survey', 302],
            [[SurveyClosedFixtures::class], '/domestic-survey/contact-and-business-details', 302, '/domestic-survey/completed'],
            [[SurveyClosedFixtures::class], '/domestic-survey/closing-details', 302, '/domestic-survey/completed'],
            [[SurveyClosedFixtures::class], '/domestic-survey/close-exempt', 302, '/domestic-survey/completed'],
            [[SurveyClosedFixtures::class], '/domestic-survey/close-not-in-possession', 302, '/domestic-survey/completed'],
            [[SurveyClosedFixtures::class], '/domestic-survey/completed', 200],

            [[ResponseOnHireButInProgress::class], '/domestic-survey', 200],
            [[ResponseOnHireButInProgress::class], '/domestic-survey/contact-and-business-details', 302, '/domestic-survey'],
            [[ResponseOnHireButInProgress::class], '/domestic-survey/closing-details', 403],
            [[ResponseOnHireButInProgress::class], '/domestic-survey/close-exempt', 403],
            [[ResponseOnHireButInProgress::class], '/domestic-survey/close-not-in-possession', 200],
            [[ResponseOnHireButInProgress::class], '/domestic-survey/completed', 302, '/domestic-survey'],

            [[ResponseSoldButInProgressFixtures::class], '/domestic-survey', 200],
            [[ResponseSoldButInProgressFixtures::class], '/domestic-survey/contact-and-business-details', 302, '/domestic-survey'],
            [[ResponseSoldButInProgressFixtures::class], '/domestic-survey/closing-details', 403],
            [[ResponseSoldButInProgressFixtures::class], '/domestic-survey/close-exempt', 403],
            [[ResponseSoldButInProgressFixtures::class], '/domestic-survey/close-not-in-possession', 200],
            [[ResponseSoldButInProgressFixtures::class], '/domestic-survey/completed', 302, '/domestic-survey'],

            [[ResponseScrappedButInProgressFixtures::class], '/domestic-survey', 200],
            [[ResponseScrappedButInProgressFixtures::class], '/domestic-survey/contact-and-business-details', 302, '/domestic-survey'],
            [[ResponseScrappedButInProgressFixtures::class], '/domestic-survey/closing-details', 403],
            [[ResponseScrappedButInProgressFixtures::class], '/domestic-survey/close-exempt', 403],
            [[ResponseScrappedButInProgressFixtures::class], '/domestic-survey/close-not-in-possession', 200],
            [[ResponseScrappedButInProgressFixtures::class], '/domestic-survey/completed', 302, '/domestic-survey'],

            [[ResponseExemptVehicleTypeFixtures::class], '/domestic-survey', 200],
            [[ResponseExemptVehicleTypeFixtures::class], '/domestic-survey/contact-and-business-details', 302, '/domestic-survey'],
            [[ResponseExemptVehicleTypeFixtures::class], '/domestic-survey/closing-details', 403],
            [[ResponseExemptVehicleTypeFixtures::class], '/domestic-survey/close-exempt', 200],
            [[ResponseExemptVehicleTypeFixtures::class], '/domestic-survey/close-not-in-possession', 403],
            [[ResponseExemptVehicleTypeFixtures::class], '/domestic-survey/completed', 302, '/domestic-survey'],

            // Business + vehicle fixtures not yet completed
            [[ResponseFixtures::class], '/domestic-survey', 200],
            [[ResponseFixtures::class], '/domestic-survey/contact-and-business-details', 302, '/domestic-survey'],
            [[ResponseFixtures::class], '/domestic-survey/closing-details', 403],
            [[ResponseFixtures::class], '/domestic-survey/close-exempt', 403],
            [[ResponseFixtures::class], '/domestic-survey/close-not-in-possession', 403],
            [[ResponseFixtures::class], '/domestic-survey/completed', 302, '/domestic-survey'],

            [[VehicleFixtures::class], '/domestic-survey', 200],
            [[VehicleFixtures::class], '/domestic-survey/contact-and-business-details', 200],
            [[VehicleFixtures::class], '/domestic-survey/closing-details', 302, '/domestic-survey/closing-details/start'],
            [[VehicleFixtures::class], '/domestic-survey/close-exempt', 403],
            [[VehicleFixtures::class], '/domestic-survey/close-not-in-possession', 403],
            [[VehicleFixtures::class], '/domestic-survey/completed', 302, '/domestic-survey'],
        ];
    }

    /**
     * @dataProvider dataNewSurveyPageAccess
     */
    public function testNewSurveyPageAccess(array $fixtures, string $url, int $expectedCode, ?string $expectedLocation=null): void
    {
        $browser = $this->getBrowserLoadFixturesAndLogin($fixtures);
        $browser->followRedirects(false);

        $browser->request('GET', $url);

        $this->assertEquals($expectedCode, $browser->getResponse()->getStatusCode());

        if ($expectedCode === 302 && $expectedLocation !== null) {
            $location = $browser->getResponse()->headers->get('location');
            $this->assertEquals($expectedLocation, $location);
        };
    }
}