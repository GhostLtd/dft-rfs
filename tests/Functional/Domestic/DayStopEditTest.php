<?php

namespace App\Tests\Functional\Domestic;

use App\Entity\AbstractGoodsDescription;
use App\Entity\CargoType;
use App\Entity\Distance;
use App\Entity\Domestic\Day;
use App\Entity\HazardousGoods;
use App\Tests\DataFixtures\Domestic\DayStopFixtures;
use App\Tests\Functional\AbstractWizardTest;
use App\Tests\Functional\Wizard\Domestic\CallbackDayDatabaseTestCase;
use App\Tests\Functional\Wizard\FormTestCase;
use App\Tests\Functional\Wizard\WizardEndUrlTestCase;
use App\Tests\Functional\Wizard\WizardStepUrlTestCase;

class DayStopEditTest extends AbstractWizardTest
{
    protected string $baseUrl = "/domestic-survey/day-1/stop-1";

    public function wizardData(): array
    {
        return [
            'Change start/destination' => [
                '/stage-start',
                [DayStopFixtures::class], // extra fixtures
                [
                    new WizardStepUrlTestCase("{$this->baseUrl}/stage-start", "origin_continue", [
                        new FormTestCase([
                            "origin" => [
                                "originLocation" => "",
                                "goodsLoaded" => "0",
                            ]
                        ], [
                            "#origin_originLocation",
                        ]),
                        new FormTestCase([
                            "origin" => [
                                "originLocation" => "Worthing",
                                "goodsLoaded" => "0",
                            ]
                        ]),
                    ]),
                    new WizardStepUrlTestCase("{$this->baseUrl}/stage-end", "destination_continue", [
                        new FormTestCase([
                            "destination" => [
                                "destinationLocation" => "",
                                "goodsUnloaded" => "0",
                            ]
                        ], [
                            "#destination_destinationLocation",
                        ]),
                        new FormTestCase([
                            "destination" => [
                                "destinationLocation" => "Portsmouth",
                                "goodsUnloaded" => "0",
                            ]
                        ]),
                    ]),
                    new WizardStepUrlTestCase("{$this->baseUrl}/border-crossing", "border_crossing_continue", [
                        new FormTestCase([
                            "border_crossing" => [
                                "borderCrossed" => 1,
                                "borderCrossingLocation" => "Littlehampton",
                            ]
                        ]),
                    ]),
                    new WizardEndUrlTestCase("/domestic-survey/day-1"),
                    new CallbackDayDatabaseTestCase(1, function(Day $day) {
                        $dayStop = $day->getStopByNumber(1);

                        $this->assertNotNull($dayStop);
                        $this->assertEquals("Worthing", $dayStop->getOriginLocation());
                        $this->assertEquals("Portsmouth", $dayStop->getDestinationLocation());
                        $this->assertEquals(false, $dayStop->getGoodsLoadedIsPort());
                        $this->assertEquals(false, $dayStop->getGoodsUnloadedIsPort());
                        $this->assertEquals(null, $dayStop->getGoodsTransferredFrom());
                        $this->assertEquals(null, $dayStop->getGoodsTransferredTo());
                        $this->assertEquals("Littlehampton", $dayStop->getBorderCrossingLocation());
                    })
                ],
            ],
            'Change start/destination with loading/unloading' => [
                '/stage-start',
                [DayStopFixtures::class], // extra fixtures
                [
                    new WizardStepUrlTestCase("{$this->baseUrl}/stage-start", "origin_continue", [
                        new FormTestCase([
                            "origin" => [
                                "originLocation" => "",
                                "goodsLoaded" => "1",
                                "goodsTransferredFrom" => Day::TRANSFERRED_RAIL,
                            ]
                        ], [
                            "#origin_originLocation",
                        ]),
                        new FormTestCase([
                            "origin" => [
                                "originLocation" => "Brighton",
                                "goodsLoaded" => "1",
                                "goodsTransferredFrom" => Day::TRANSFERRED_RAIL,
                            ]
                        ]),
                    ]),
                    new WizardStepUrlTestCase("{$this->baseUrl}/stage-end", "destination_continue", [
                        new FormTestCase([
                            "destination" => [
                                "destinationLocation" => "",
                                "goodsUnloaded" => "1",
                                "goodsTransferredTo" => Day::TRANSFERRED_AIR,
                            ]
                        ], [
                            "#destination_destinationLocation",
                        ]),
                        new FormTestCase([
                            "destination" => [
                                "destinationLocation" => "Havant",
                                "goodsUnloaded" => "1",
                                "goodsTransferredTo" => Day::TRANSFERRED_AIR,
                            ]
                        ]),
                    ]),
                    new WizardStepUrlTestCase("{$this->baseUrl}/border-crossing", "border_crossing_continue", [
                        new FormTestCase([
                            "border_crossing" => [
                                "borderCrossed" => 1,
                                "borderCrossingLocation" => "Felpham",
                            ]
                        ]),
                    ]),
                    new WizardEndUrlTestCase("/domestic-survey/day-1"),
                    new CallbackDayDatabaseTestCase(1, function(Day $day) {
                        $dayStop = $day->getStopByNumber(1);

                        $this->assertNotNull($dayStop);
                        $this->assertEquals("Brighton", $dayStop->getOriginLocation());
                        $this->assertEquals("Havant", $dayStop->getDestinationLocation());
                        $this->assertEquals(true, $dayStop->getGoodsLoadedIsPort());
                        $this->assertEquals(true, $dayStop->getGoodsUnloadedIsPort());
                        $this->assertEquals(Day::TRANSFERRED_RAIL, $dayStop->getGoodsTransferredFrom());
                        $this->assertEquals(Day::TRANSFERRED_AIR, $dayStop->getGoodsTransferredTo());
                        $this->assertEquals("Felpham", $dayStop->getBorderCrossingLocation());
                    })
                ],
            ],
            'Change distance' => [
                '/distance-travelled',
                [DayStopFixtures::class], // extra fixtures
                [
                    new WizardStepUrlTestCase("{$this->baseUrl}/distance-travelled", "distance_travelled_continue", [
                        new FormTestCase([
                            "distance_travelled" => [
                                "distanceTravelled" => [
                                    "value" => "",
                                    "unit" => Distance::UNIT_MILES,
                                ],
                            ]
                        ], [
                            "#distance_travelled_distanceTravelled_value",
                        ]),
                        new FormTestCase([
                            "distance_travelled" => [
                                "distanceTravelled" => [
                                    "value" => "76",
                                    "unit" => Distance::UNIT_MILES,
                                ],
                            ]
                        ]),
                    ]),
                    new WizardEndUrlTestCase("/domestic-survey/day-1"),
                    new CallbackDayDatabaseTestCase(1, function(Day $day) {
                        $dayStop = $day->getStopByNumber(1);

                        $this->assertNotNull($dayStop);
                        $distance = $dayStop->getDistanceTravelled();

                        $this->assertNotNull($distance);

                        $this->assertEquals(76, $distance->getValue());
                        $this->assertEquals(Distance::UNIT_MILES, $distance->getUnit());
                    })
                ],
            ],
            'Change goods description: Empty' => [
                '/goods-description',
                [DayStopFixtures::class], // extra fixtures
                [
                    new WizardStepUrlTestCase("{$this->baseUrl}/goods-description", "goods_description_continue", [
                        new FormTestCase([
                            "goods_description" => [
                                "goodsDescription" => AbstractGoodsDescription::GOODS_DESCRIPTION_EMPTY,
                            ],
                        ]),
                    ]),
                    new WizardEndUrlTestCase("/domestic-survey/day-1"),
                    new CallbackDayDatabaseTestCase(1, function(Day $day) {
                        $dayStop = $day->getStopByNumber(1);

                        $this->assertNotNull($dayStop);
                        $this->assertEquals(AbstractGoodsDescription::GOODS_DESCRIPTION_EMPTY, $dayStop->getGoodsDescription());
                        $this->assertEquals(null, $dayStop->getGoodsDescriptionOther());
                        $this->assertEquals(null, $dayStop->getHazardousGoodsCode());
                        $this->assertEquals(null, $dayStop->getCargoTypeCode());
                        $this->assertEquals(null, $dayStop->getWeightOfGoodsCarried());
                        $this->assertEquals(null, $dayStop->getWasAtCapacity());
                        $this->assertEquals(null, $dayStop->getWasLimitedByWeight());
                        $this->assertEquals(null, $dayStop->getWasLimitedBySpace());
                    })
                ],
            ],
            'Change goods description: Groupage' => [
                '/goods-description',
                [DayStopFixtures::class], // extra fixtures
                [
                    new WizardStepUrlTestCase("{$this->baseUrl}/goods-description", "goods_description_continue", [
                        new FormTestCase([
                            "goods_description" => [
                                "goodsDescription" => AbstractGoodsDescription::GOODS_DESCRIPTION_GROUPAGE,
                            ],
                        ]),
                    ]),
                    new WizardStepUrlTestCase("{$this->baseUrl}/hazardous-goods", "hazardous_goods_continue", [
                        new FormTestCase([
                            "hazardous_goods" => [
                                "isHazardousGoods" => "1",
                                "hazardousGoodsCode" => HazardousGoods::CODE_7_RADIOACTIVE_MATERIAL,
                            ],
                        ]),
                    ]),
                    new WizardStepUrlTestCase("{$this->baseUrl}/cargo-type", "cargo_type_continue", [
                        new FormTestCase([
                            "cargo_type" => [
                                "cargoTypeCode" => CargoType::CODE_PL_PALLETISED_GOODS,
                            ],
                        ]),
                    ]),
                    new WizardStepUrlTestCase("{$this->baseUrl}/goods-weight", "goods_weight_continue", [
                        new FormTestCase([
                            "goods_weight" => [
                                "weightOfGoodsCarried" => null,
                                "wasAtCapacity" => "1",
                                "wasLimitedBy" => [
                                    1 => "weight",
                                ],
                            ],
                        ], [
                            "#goods_weight_weightOfGoodsCarried",
                        ]),
                        new FormTestCase([
                            "goods_weight" => [
                                "weightOfGoodsCarried" => "7613",
                                "wasAtCapacity" => "1",
                                "wasLimitedBy" => [
                                    1 => "weight",
                                ],
                            ],
                        ]),
                    ]),
                    new WizardEndUrlTestCase("/domestic-survey/day-1"),
                    new CallbackDayDatabaseTestCase(1, function(Day $day) {
                        $dayStop = $day->getStopByNumber(1);

                        $this->assertNotNull($dayStop);
                        $this->assertEquals(AbstractGoodsDescription::GOODS_DESCRIPTION_GROUPAGE, $dayStop->getGoodsDescription());
                        $this->assertEquals(null, $dayStop->getGoodsDescriptionOther());
                        $this->assertEquals(HazardousGoods::CODE_7_RADIOACTIVE_MATERIAL, $dayStop->getHazardousGoodsCode());
                        $this->assertEquals(CargoType::CODE_PL_PALLETISED_GOODS, $dayStop->getCargoTypeCode());
                        $this->assertEquals("7613", $dayStop->getWeightOfGoodsCarried());
                        $this->assertEquals(true, $dayStop->getWasAtCapacity());
                        $this->assertEquals(true, $dayStop->getWasLimitedByWeight());
                        $this->assertEquals(false, $dayStop->getWasLimitedBySpace());
                    })
                ],
            ],
            'Change goods weight' => [
                '/goods-weight',
                [DayStopFixtures::class], // extra fixtures
                [
                    new WizardStepUrlTestCase("{$this->baseUrl}/goods-weight", "goods_weight_continue", [
                        new FormTestCase([
                            "goods_weight" => [
                                "weightOfGoodsCarried" => null,
                                "wasAtCapacity" => "1",
                                "wasLimitedBy" => [
                                    0 => "space",
                                    1 => "weight",
                                ],
                            ],
                        ], [
                            "#goods_weight_weightOfGoodsCarried",
                        ]),
                        new FormTestCase([
                            "goods_weight" => [
                                "weightOfGoodsCarried" => "8843",
                                "wasAtCapacity" => "1",
                                "wasLimitedBy" => [
                                    0 => "space",
                                    1 => "weight",
                                ],
                            ],
                        ]),
                    ]),
                    new WizardEndUrlTestCase("/domestic-survey/day-1"),
                    new CallbackDayDatabaseTestCase(1, function(Day $day) {
                        $dayStop = $day->getStopByNumber(1);

                        $this->assertNotNull($dayStop);
                        $this->assertEquals("8843", $dayStop->getWeightOfGoodsCarried());
                        $this->assertEquals(true, $dayStop->getWasAtCapacity());
                        $this->assertEquals(true, $dayStop->getWasLimitedByWeight());
                        $this->assertEquals(true, $dayStop->getWasLimitedBySpace());
                    })
                ],
            ],
        ];
    }

    /**
     * @dataProvider wizardData
     */
    public function testDayStopEditWizards($startRelativeUrl, $fixtures, $wizardData)
    {
        $browser = $this->getBrowserLoadFixturesAndLogin($fixtures);
        $browser->request('GET', "{$this->baseUrl}{$startRelativeUrl}");

        $this->doWizardTest($browser, $wizardData);
    }
}