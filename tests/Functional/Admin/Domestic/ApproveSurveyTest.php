<?php

namespace App\Tests\Functional\Admin\Domestic;

use App\Tests\DataFixtures\Domestic\DayStopFixtures;
use App\Tests\DataFixtures\Domestic\ResponseFixtures;
use App\Tests\DataFixtures\Domestic\ResponseOnMultiHireFixtures;
use App\Tests\DataFixtures\Domestic\ResponseScrappedFixtures;
use App\Tests\DataFixtures\Domestic\ResponseSoldFixtures;
use App\Tests\DataFixtures\Domestic\SurveyClosedFixtures;
use App\Tests\DataFixtures\Domestic\VehicleFixtures;
use App\Tests\Functional\AbstractAdminFunctionalTest;

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
}
