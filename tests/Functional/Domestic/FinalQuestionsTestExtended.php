<?php

namespace App\Tests\Functional\Domestic;

use App\Entity\SurveyInterface;
use App\Tests\DataFixtures\Domestic\DayFullOfSummaryFixtures;
use App\Tests\DataFixtures\Domestic\DayStopFixtures;
use App\Tests\DataFixtures\Domestic\DaySummaryFixtures;
use App\Tests\DataFixtures\Domestic\VehicleFixtures;
use App\Tests\Functional\AbstractWizardTest;
use App\Tests\Functional\Wizard\FinalQuestionsDatabaseTestCase;

class FinalQuestionsTestExtended extends AbstractWizardTest
{
    public function wizardData(string $state): array
    {
        return array_merge(
                FinalQuestionsData::finalQuestionsData($state),
                [
                    new FinalQuestionsDatabaseTestCase(
                        SurveyInterface::STATE_CLOSED,
                        $state === FinalQuestionsData::STATE_EMPTY ? 'repair' : null,
                        $state === FinalQuestionsData::STATE_EMPTY ? null : 30,
                        $state === FinalQuestionsData::STATE_EMPTY ? null : 'litres',
                    )
                ]
            );
    }

    public function testFinalQuestionsWithSomeMissingDaysWizard()
    {
        $browser = $this->getBrowserLoadFixturesAndLogin([DaySummaryFixtures::class, DayStopFixtures::class]);
        $browser->request('GET', '/domestic-survey/closing-details/start');

        $this->doWizardTest($browser, self::wizardData(FinalQuestionsData::STATE_PARTIAL));
    }

    public function testFinalQuestionsWithEmptySurveyWizard()
    {
        $browser = $this->getBrowserLoadFixturesAndLogin([VehicleFixtures::class]);
        $browser->request('GET', '/domestic-survey/closing-details/start');

        $this->doWizardTest($browser, self::wizardData(FinalQuestionsData::STATE_EMPTY));
    }

    public function testFinalQuestionsWithFilledDayWizard()
    {
        $browser = $this->getBrowserLoadFixturesAndLogin([DayFullOfSummaryFixtures::class]);
        $browser->request('GET', '/domestic-survey/closing-details/start');

        $this->doWizardTest($browser, self::wizardData(FinalQuestionsData::STATE_FILLED));
    }
}