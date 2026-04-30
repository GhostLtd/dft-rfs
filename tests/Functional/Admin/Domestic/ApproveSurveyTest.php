<?php

namespace App\Tests\Functional\Admin\Domestic;

use App\Tests\DataFixtures\Domestic\DayStopFixtures;
use App\Tests\DataFixtures\Domestic\ResponseFixtures;
use App\Tests\DataFixtures\Domestic\ResponseOnMultiHireFixtures;
use App\Tests\DataFixtures\Domestic\ResponseScrappedFixtures;
use App\Tests\DataFixtures\Domestic\ResponseSoldFixtures;
use App\Tests\DataFixtures\Domestic\SurveyClosedFixtures;
use App\Tests\DataFixtures\Domestic\VehicleFixtures;
use App\Tests\Functional\Admin\AbstractAdminFunctionalTest;

class ApproveSurveyTest extends AbstractAdminFunctionalTest
{
    public const BUTTON_ONLY_FORM_NAME = 'confirm_action';
    public const FINAL_DETAILS_FORM_NAME = 'final_details';
    public const UNFILLED_SURVEY_FORM_NAME = 'confirm_approved_action';

    public function dataProvider(): array
    {
        return [
            [
                [SurveyClosedFixtures::class, ResponseFixtures::class],
                self::UNFILLED_SURVEY_FORM_NAME,
            ],
            [
                [ResponseScrappedFixtures::class],
                self::BUTTON_ONLY_FORM_NAME,
            ],
            [
                [ResponseSoldFixtures::class],
                self::BUTTON_ONLY_FORM_NAME,
            ],
            // N.B. Normal hire doesn't have an "approve" button (it can only be re-issued, re-opened or rejected)
            [
                [ResponseOnMultiHireFixtures::class],
                self::BUTTON_ONLY_FORM_NAME,
            ],
            [
                [SurveyClosedFixtures::class, DayStopFixtures::class],
                self::BUTTON_ONLY_FORM_NAME,
            ],
            [
                [SurveyClosedFixtures::class, VehicleFixtures::class],
                self::FINAL_DETAILS_FORM_NAME,
            ],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testApprovalForm(array $fixtures, string $expectedFormName)
    {
        $browser = $this->getBrowserLoadFixturesAndLogin($fixtures);

        $browser->request('GET', '/csrgt/surveys-ni/');
        $browser->clickLink('view survey for AB01ABC started 01/05/2021');

        $crawler = $browser->clickLink('Approve survey');

        $formName = $crawler->filterXPath('//form')->attr('name');

        $this->assertEquals($expectedFormName, $formName);
    }

    /**
     * Asserts the rendered approve form has the bundle's button_group
     * structure (cancel-as-link rather than button) and the right
     * survey-specific fields for each form-class scenario.
     *
     * @dataProvider dataProvider
     */
    public function testApprovalFormRendersCorrectly(array $fixtures, string $expectedFormName): void
    {
        $browser = $this->getBrowserLoadFixturesAndLogin($fixtures);

        $browser->request('GET', '/csrgt/surveys-ni/');
        $browser->clickLink('view survey for AB01ABC started 01/05/2021');
        $crawler = $browser->clickLink('Approve survey');

        self::assertEquals(200, $browser->getResponse()->getStatusCode());

        // Bundle's ConfirmActionType produces a submit button + a cancel anchor.
        // Don't assert specific text — labels are per-action translation keys
        // (e.g. "Approve survey", "Yes, delete this stage") and vary by flow.
        self::assertGreaterThanOrEqual(
            1,
            $crawler->filterXPath('//form//button[@type="submit"]')->count(),
            'Form should have a submit button (the confirm)'
        );
        self::assertGreaterThanOrEqual(
            1,
            $crawler->filterXPath('//form//a')->count(),
            'Form should have a cancel <a> link (cancel is now LinkType, not ButtonType)'
        );

        // Form-specific fields
        match($expectedFormName) {
            self::UNFILLED_SURVEY_FORM_NAME => $this->assertHasUnfilledSurveyField($crawler),
            self::FINAL_DETAILS_FORM_NAME => $this->assertHasEmptySurveyField($crawler),
            self::BUTTON_ONLY_FORM_NAME => $this->assertNoExtraSurveyFields($crawler),
        };
    }

    private function assertHasUnfilledSurveyField(\Symfony\Component\DomCrawler\Crawler $crawler): void
    {
        self::assertGreaterThanOrEqual(
            1,
            $crawler->filterXPath('//*[@name="confirm_approved_action[reasonForUnfilledSurvey]"]')->count(),
            'ConfirmApprovedActionType should render reasonForUnfilledSurvey choice field'
        );
    }

    private function assertHasEmptySurveyField(\Symfony\Component\DomCrawler\Crawler $crawler): void
    {
        self::assertGreaterThanOrEqual(
            1,
            $crawler->filterXPath('//*[contains(@name, "[reasonForEmptySurvey]")]')->count(),
            'FinalDetailsType should render reasonForEmptySurvey choice field'
        );
    }

    private function assertNoExtraSurveyFields(\Symfony\Component\DomCrawler\Crawler $crawler): void
    {
        self::assertCount(
            0,
            $crawler->filterXPath('//*[contains(@name, "[reasonForUnfilledSurvey]") or contains(@name, "[reasonForEmptySurvey]")]'),
            'Plain ConfirmActionType should not render survey-specific reason fields'
        );
    }
}
