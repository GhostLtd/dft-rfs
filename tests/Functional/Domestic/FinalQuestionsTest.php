<?php

namespace App\Tests\Functional\Domestic;

use App\Tests\DataFixtures\DayFullOfSummaryFixtures;
use App\Tests\DataFixtures\DayStopFixtures;
use App\Tests\DataFixtures\DaySummaryFixtures;
use App\Tests\DataFixtures\VehicleFixtures;
use App\Tests\Functional\AbstractWizardTest;

class FinalQuestionsTest extends AbstractWizardTest
{
    public function testFinalQuestionsWithSomeMissingDaysWizard()
    {
        $browser = $this->getBrowserLoadFixturesAndLogin([DaySummaryFixtures::class, DayStopFixtures::class]);
        $browser->request('GET', '/domestic-survey/closing-details/start');

        $this->doWizardTest($browser, FinalQuestionsData::finalQuestionsData(FinalQuestionsData::STATE_PARTIAL));
    }

    public function testFinalQuestionsWithEmptySurveyWizard()
    {
        $browser = $this->getBrowserLoadFixturesAndLogin([VehicleFixtures::class]);
        $browser->request('GET', '/domestic-survey/closing-details/start');

        $this->doWizardTest($browser, FinalQuestionsData::finalQuestionsData(FinalQuestionsData::STATE_EMPTY));
    }

    public function testFinalQuestionsWithFilledDayWizard()
    {
        $browser = $this->getBrowserLoadFixturesAndLogin([DayFullOfSummaryFixtures::class]);
        $browser->request('GET', '/domestic-survey/closing-details/start');

        $this->doWizardTest($browser, FinalQuestionsData::finalQuestionsData(FinalQuestionsData::STATE_FILLED));
    }
}