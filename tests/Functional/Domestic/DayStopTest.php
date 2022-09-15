<?php

namespace App\Tests\Functional\Domestic;

use App\Tests\DataFixtures\Domestic\BusinessFixtures;
use App\Tests\Functional\AbstractWizardTest;

class DayStopTest extends AbstractWizardTest
{
    public function wizardData(): array
    {
        return [
            'Goods loaded' => [DayStopData::dayStopData(true)],
            'No goods loaded' => [DayStopData::dayStopData(false)],
            'Goods unloaded' => [DayStopData::dayStopData(true, true)],
            'No goods unloaded' => [DayStopData::dayStopData(true, false)],
            'Empty' => [DayStopData::dayStopData(true, true, true)],
            'Bread' => [DayStopData::dayStopData(true, true, false)],
            'Hazardous' => [DayStopData::dayStopData(true, true, false, true)],
            'Non-hazardous' => [DayStopData::dayStopData(true, true, false, false)],
            'Not at capacity' => [DayStopData::dayStopData(true, true, false, false, false, false)],
            'At capacity by space' => [DayStopData::dayStopData(true, true, false, false, true, false)],
            'At capacity by weight' => [DayStopData::dayStopData(true, true, false, false, false, true)],
            'At capacity by space and weight' => [DayStopData::dayStopData(true, true, false, false, true, true)],
        ];
    }

    /**
     * @dataProvider wizardData
     */
    public function testDayStopWizard($wizardData)
    {
        $browser = $this->getBrowserLoadFixturesAndLogin([BusinessFixtures::class]);
        $browser->request('GET', '/domestic-survey/day-1');

        $this->doWizardTest($browser, $wizardData);
    }
}