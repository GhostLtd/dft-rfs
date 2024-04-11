<?php

namespace App\Tests\NewFunctional\Wizard\Domestic;

use App\Tests\DataFixtures\Domestic\DayStopFixtures;
use App\Tests\DataFixtures\Domestic\DaySummaryFixtures;
use App\Tests\NewFunctional\Wizard\AbstractPasscodeWizardTest;

class DomesticSurveyDayAddMischiefTest extends AbstractPasscodeWizardTest
{
    public function testSummaryDayThenAddStopSwitcharooFails(): void
    {
        $this->initialiseTest([DaySummaryFixtures::class]);
        $this->pathTestAction('/domestic-survey');

        $this->clickLinkContaining('Change', 1);
        $this->assertStringContainsString('Summary day', $this->getHeading('h2'));

        $this->client->request('GET', '/domestic-survey/day-2/stop-add');
        $this->assertPathMatches('/domestic-survey/day-2', false);
    }

    public function testSummaryDayThenAddSwitcharooFails(): void
    {
        $this->initialiseTest([DaySummaryFixtures::class]);
        $this->pathTestAction('/domestic-survey');

        $this->clickLinkContaining('Change', 1);
        $this->assertStringContainsString('Summary day', $this->getHeading('h2'));

        $this->client->request('GET', '/domestic-survey/day-2/add');
        $this->assertPathMatches('/domestic-survey/day-2', false);
    }

    public function testDetailDayThenAddSummarySwitcharooFails(): void
    {
        $this->initialiseTest([DayStopFixtures::class]);
        $this->pathTestAction('/domestic-survey');

        $this->clickLinkContaining('Change', 0);
        $this->assertStringContainsString('Detailed day', $this->getHeading('h2'));

        $this->client->request('GET', '/domestic-survey/day-1/summary');
        $this->assertPathMatches('/domestic-survey/day-1', false);
    }

    public function testDetailDayThenAddSwitcharooFails(): void
    {
        $this->initialiseTest([DayStopFixtures::class]);
        $this->pathTestAction('/domestic-survey');

        $this->clickLinkContaining('Change', 0);
        $this->assertStringContainsString('Detailed day', $this->getHeading('h2'));

        $this->client->request('GET', '/domestic-survey/day-1/add');
        $this->assertPathMatches('/domestic-survey/day-1', false);
    }
}
