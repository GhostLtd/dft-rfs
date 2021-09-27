<?php

namespace App\Tests\Functional\Domestic;

use App\Tests\DataFixtures\Domestic\BusinessFixtures;
use App\Tests\Functional\AbstractWizardTest;

class DaySummaryTest extends AbstractWizardTest
{
    public function wizardData(): array
    {
        return [
            'Goods loaded' => [DaySummaryData::daySummaryData(true)],
            'No goods loaded' => [DaySummaryData::daySummaryData(false)],
            'Goods unloaded' => [DaySummaryData::daySummaryData(true, true)],
            'No goods unloaded' => [DaySummaryData::daySummaryData(true, false)],
            'Hazardous' => [DaySummaryData::daySummaryData(true, true, true)],
            'Non-hazardous' => [DaySummaryData::daySummaryData(true, true, false)],
        ];
    }

    /**
     * @dataProvider wizardData
     */
    public function testDaySummaryWizard($wizardData)
    {
        $browser = $this->getBrowserLoadFixturesAndLogin([BusinessFixtures::class]);
        $browser->request('GET', '/domestic-survey/day-1');

        $this->doWizardTest($browser, $wizardData);
    }
}