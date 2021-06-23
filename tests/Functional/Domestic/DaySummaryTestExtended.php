<?php

namespace App\Tests\Functional\Domestic;

use App\Tests\DataFixtures\BusinessFixtures;
use App\Tests\Functional\AbstractWizardTest;
use App\Tests\Functional\Wizard\DaySummaryDatabaseTestCase;

class DaySummaryTestExtended extends AbstractWizardTest
{
    public function wizardData(): array
    {
        $data = [];

        foreach ([true, false] as $goodsLoaded) {
            foreach ([true, false] as $goodsUnloaded) {
                    foreach ([true, false] as $isHazardous) {
                        $testData = DaySummaryData::daySummaryData($goodsLoaded, $goodsUnloaded, $isHazardous);
                        $testData[] = new DaySummaryDatabaseTestCase($goodsLoaded, $goodsUnloaded, $isHazardous);
                        $data[$this->getTestName($goodsLoaded, $goodsUnloaded, $isHazardous)] = [$testData];
                }
            }
        }

        return $data;
    }

    protected function getTestName(bool $goodsLoaded, bool $goodsUnloaded=null, bool $isHazardous=null): string
    {
        $parts = [];
        $parts[] = $goodsLoaded ? 'Goods loaded' : 'No goods loaded';

        if ($goodsUnloaded !== null) {
            $parts[] = $goodsUnloaded ? 'Goods unloaded' : 'No goods unloaded';

            if ($isHazardous !== null) {
                $parts[] = $isHazardous ? "Hazardous" : "Non-hazardous";
            }
        }

        return join(', ', $parts);
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