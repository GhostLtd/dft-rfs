<?php

namespace App\Tests\Functional\Domestic;

use App\Entity\AbstractGoodsDescription;
use App\Entity\Distance;
use App\Entity\HazardousGoods;
use App\Tests\DataFixtures\Domestic\BusinessFixtures;
use App\Tests\Functional\AbstractWizardTest;
use App\Tests\Functional\Wizard\DayStopDatabaseTestCase;

class DayStopTestExtended extends AbstractWizardTest
{
    public function wizardData(): array
    {
        $data = [];

        foreach ([true, false] as $goodsLoaded) {
            foreach ([true, false] as $goodsUnloaded) {
                foreach ([true, false] as $isEmpty) {
                    if (!$isEmpty) {
                        foreach ([true, false] as $isHazardous) {
                            foreach([true, false] as $atCapacityBySpace) {
                                foreach([true, false] as $atCapacityByWeight) {
                                    $testData = DayStopData::dayStopData($goodsLoaded, $goodsUnloaded, $isEmpty, $isHazardous, $atCapacityBySpace, $atCapacityByWeight);
                                    $testData[] = new DayStopDatabaseTestCase(
                                        $goodsLoaded,
                                        'Portsmouth',
                                        $goodsUnloaded,
                                        'Worthing',
                                        'Hove',
                                        120,
                                        Distance::UNIT_MILES,
                                        AbstractGoodsDescription::GOODS_DESCRIPTION_OTHER,
                                        "Bread",
                                        $isHazardous ? HazardousGoods::CODE_7_RADIOACTIVE_MATERIAL : HazardousGoods::CODE_0_NOT_HAZARDOUS,
                                        "RC",
                                        100,
                                        $atCapacityBySpace,
                                        $atCapacityByWeight
                                    );
                                    $data[$this->getTestName($goodsLoaded, $goodsUnloaded, $isEmpty, $isHazardous, $atCapacityBySpace, $atCapacityByWeight)] = [$testData];
                                }
                            }
                        }
                    } else {
                        $testData = DayStopData::dayStopData($goodsLoaded, $goodsUnloaded, true);
                        $testData[] = new DayStopDatabaseTestCase(
                            $goodsLoaded,
                            'Portsmouth',
                            $goodsUnloaded,
                            'Worthing',
                            'Hove',
                            120,
                            Distance::UNIT_MILES,
                            AbstractGoodsDescription::GOODS_DESCRIPTION_EMPTY,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                        );
                        $data[$this->getTestName($goodsLoaded, $goodsUnloaded, true)] = [$testData];
                    }
                }
            }
        }

        return $data;
    }

    protected function getTestName(bool $goodsLoaded, bool $goodsUnloaded, bool $isEmpty, bool $isHazardous=null, bool $atCapacityBySpace=null, bool $atCapacityByWeight=null): string
    {
        $parts = [];
        $parts[] = $goodsLoaded ? 'Goods loaded' : 'No goods loaded';
        $parts[] = $goodsUnloaded ? 'Goods unloaded' : 'No goods unloaded';
        $parts[] = $isEmpty ? "Empty" : "Bread";

        if (!$isEmpty) {
            $parts[] = $isHazardous ? "Hazardous" : "Non-hazardous";

            if (!$atCapacityByWeight && !$atCapacityBySpace) {
                $parts[] = "Not at capacity";
            } else {
                $capacityParts = [];
                if ($atCapacityBySpace) {
                    $capacityParts[] = "space";
                }
                if ($atCapacityByWeight) {
                    $capacityParts[] = "weight";
                }

                $parts[] = "At capacity by ".join(" + ", $capacityParts);
            }
        }

        return join(', ', $parts);
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