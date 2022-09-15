<?php

namespace App\Tests\Functional\Domestic;

use App\Entity\AbstractGoodsDescription;
use App\Entity\Distance;
use App\Entity\HazardousGoods;
use App\Tests\DataFixtures\Domestic\BusinessFixtures;
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
                        $testData[] = new DaySummaryDatabaseTestCase(
                            $goodsLoaded,
                            'Portsmouth',
                            $goodsUnloaded,
                            'Worthing',
                            $isHazardous ? HazardousGoods::CODE_7_RADIOACTIVE_MATERIAL : HazardousGoods::CODE_0_NOT_HAZARDOUS,
                        'The Moon',
                        'Hove',
                        10,
                        Distance::UNIT_MILES,
                        16,
                        Distance::UNIT_KILOMETRES,
                        AbstractGoodsDescription::GOODS_DESCRIPTION_OTHER,
                        'Bread',
                        'RC',
                        2000,
                        1800,
                        2,
                        8,
                        0);
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