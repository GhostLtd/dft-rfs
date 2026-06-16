<?php

namespace App\Tests\Functional\Domestic;

use App\Entity\AbstractGoodsDescription;
use App\Entity\Embeddable\Distance;
use App\Entity\HazardousGoods;
use App\Tests\DataFixtures\Domestic\BusinessFixtures;
use App\Tests\Functional\AbstractWizardTestCase;
use App\Tests\Functional\Wizard\DaySummaryDatabaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class DaySummaryTestExtended extends AbstractWizardTestCase
{
    public static function wizardData(): array
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
                        '10',
                        Distance::UNIT_MILES,
                        '16',
                        Distance::UNIT_KILOMETRES,
                        AbstractGoodsDescription::GOODS_DESCRIPTION_OTHER,
                        'Bread',
                        'RC',
                        2000,
                        1800,
                        2,
                        8,
                        0);
                        $data[self::getTestName($goodsLoaded, $goodsUnloaded, $isHazardous)] = [$testData];
                }
            }
        }

        return $data;
    }

    protected static function getTestName(bool $goodsLoaded, bool $goodsUnloaded=null, bool $isHazardous=null): string
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

    #[DataProvider('wizardData')]
    public function testDaySummaryWizard($wizardData)
    {
        $browser = $this->getBrowserLoadFixturesAndLogin([BusinessFixtures::class]);
        $browser->request('GET', '/domestic-survey/day-1');

        $this->doWizardTest($browser, $wizardData);
    }
}