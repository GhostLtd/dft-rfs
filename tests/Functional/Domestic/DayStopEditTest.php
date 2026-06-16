<?php

namespace App\Tests\Functional\Domestic;

use App\Entity\AbstractGoodsDescription;
use App\Entity\CargoType;
use App\Entity\Domestic\Day;
use App\Entity\Embeddable\Distance;
use App\Entity\HazardousGoods;
use App\Tests\DataFixtures\Domestic\DayStopFixtures;
use App\Tests\Functional\AbstractWizardTestCase;
use App\Tests\Functional\Wizard\Domestic\CallbackDayDatabaseTestCase;
use App\Tests\Functional\Wizard\FormTestCase;
use App\Tests\Functional\Wizard\WizardEndUrlTestCase;
use App\Tests\Functional\Wizard\WizardStepUrlTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class DayStopEditTest extends AbstractWizardTestCase
{
    protected static string $baseUrl = "/domestic-survey/day-1/stop-1";

    public static function wizardData(): array
    {
        return [
            'Change start/destination' => [
                '/stage-start',
                [DayStopFixtures::class], // extra fixtures
                [
                    new WizardStepUrlTestCase(self::$baseUrl."/stage-start", "origin_button_group_continue", [
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
                    new WizardStepUrlTestCase(self::$baseUrl."/stage-end", "destination_button_group_continue", [
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
                    new WizardStepUrlTestCase(self::$baseUrl."/border-crossing", "border_crossing_button_group_continue", [
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

                        self::assertNotNull($dayStop);
                        self::assertEquals("Worthing", $dayStop->getOriginLocation());
                        self::assertEquals("Portsmouth", $dayStop->getDestinationLocation());
                        self::assertEquals(false, $dayStop->getGoodsLoadedIsPort());
                        self::assertEquals(false, $dayStop->getGoodsUnloadedIsPort());
                        self::assertEquals(null, $dayStop->getGoodsTransferredFrom());
                        self::assertEquals(null, $dayStop->getGoodsTransferredTo());
                        self::assertEquals("Littlehampton", $dayStop->getBorderCrossingLocation());
                    })
                ],
            ],
            'Change start/destination with loading/unloading' => [
                '/stage-start',
                [DayStopFixtures::class], // extra fixtures
                [
                    new WizardStepUrlTestCase(self::$baseUrl."/stage-start", "origin_button_group_continue", [
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
                    new WizardStepUrlTestCase(self::$baseUrl."/stage-end", "destination_button_group_continue", [
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
                    new WizardStepUrlTestCase(self::$baseUrl."/border-crossing", "border_crossing_button_group_continue", [
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

                        self::assertNotNull($dayStop);
                        self::assertEquals("Brighton", $dayStop->getOriginLocation());
                        self::assertEquals("Havant", $dayStop->getDestinationLocation());
                        self::assertEquals(true, $dayStop->getGoodsLoadedIsPort());
                        self::assertEquals(true, $dayStop->getGoodsUnloadedIsPort());
                        self::assertEquals(Day::TRANSFERRED_RAIL, $dayStop->getGoodsTransferredFrom());
                        self::assertEquals(Day::TRANSFERRED_AIR, $dayStop->getGoodsTransferredTo());
                        self::assertEquals("Felpham", $dayStop->getBorderCrossingLocation());
                    })
                ],
            ],
            'Change distance' => [
                '/distance-travelled',
                [DayStopFixtures::class], // extra fixtures
                [
                    new WizardStepUrlTestCase(self::$baseUrl."/distance-travelled", "distance_travelled_button_group_continue", [
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

                        self::assertNotNull($dayStop);
                        $distance = $dayStop->getDistanceTravelled();

                        self::assertNotNull($distance);

                        self::assertEquals(76, $distance->getValue());
                        self::assertEquals(Distance::UNIT_MILES, $distance->getUnit());
                    })
                ],
            ],
            'Change goods description: Empty' => [
                '/goods-description',
                [DayStopFixtures::class], // extra fixtures
                [
                    new WizardStepUrlTestCase(self::$baseUrl."/goods-description", "goods_description_button_group_continue", [
                        new FormTestCase([
                            "goods_description" => [
                                "goodsDescription" => AbstractGoodsDescription::GOODS_DESCRIPTION_EMPTY,
                            ],
                        ]),
                    ]),
                    new WizardEndUrlTestCase("/domestic-survey/day-1"),
                    new CallbackDayDatabaseTestCase(1, function(Day $day) {
                        $dayStop = $day->getStopByNumber(1);

                        self::assertNotNull($dayStop);
                        self::assertEquals(AbstractGoodsDescription::GOODS_DESCRIPTION_EMPTY, $dayStop->getGoodsDescription());
                        self::assertEquals(null, $dayStop->getGoodsDescriptionOther());
                        self::assertEquals(null, $dayStop->getHazardousGoodsCode());
                        self::assertEquals(null, $dayStop->getCargoTypeCode());
                        self::assertEquals(null, $dayStop->getWeightOfGoodsCarried());
                        self::assertEquals(null, $dayStop->getWasAtCapacity());
                        self::assertEquals(null, $dayStop->getWasLimitedByWeight());
                        self::assertEquals(null, $dayStop->getWasLimitedBySpace());
                    })
                ],
            ],
            'Change goods description: Groupage' => [
                '/goods-description',
                [DayStopFixtures::class], // extra fixtures
                [
                    new WizardStepUrlTestCase(self::$baseUrl."/goods-description", "goods_description_button_group_continue", [
                        new FormTestCase([
                            "goods_description" => [
                                "goodsDescription" => AbstractGoodsDescription::GOODS_DESCRIPTION_GROUPAGE,
                            ],
                        ]),
                    ]),
                    new WizardStepUrlTestCase(self::$baseUrl."/hazardous-goods", "hazardous_goods_button_group_continue", [
                        new FormTestCase([
                            "hazardous_goods" => [
                                "isHazardousGoods" => "yes",
                                "hazardousGoodsCode" => HazardousGoods::CODE_7_RADIOACTIVE_MATERIAL,
                            ],
                        ]),
                    ]),
                    new WizardStepUrlTestCase(self::$baseUrl."/cargo-type", "cargo_type_button_group_continue", [
                        new FormTestCase([
                            "cargo_type" => [
                                "cargoTypeCode" => CargoType::CODE_PL_PALLETISED_GOODS,
                            ],
                        ]),
                    ]),
                    new WizardStepUrlTestCase(self::$baseUrl."/goods-weight", "goods_weight_button_group_continue", [
                        new FormTestCase([
                            "goods_weight" => [
                                "weightOfGoodsCarried" => null,
                                "wasAtCapacity" => "yes",
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
                                "wasAtCapacity" => "yes",
                                "wasLimitedBy" => [
                                    1 => "weight",
                                ],
                            ],
                        ]),
                    ]),
                    new WizardEndUrlTestCase("/domestic-survey/day-1"),
                    new CallbackDayDatabaseTestCase(1, function(Day $day) {
                        $dayStop = $day->getStopByNumber(1);

                        self::assertNotNull($dayStop);
                        self::assertEquals(AbstractGoodsDescription::GOODS_DESCRIPTION_GROUPAGE, $dayStop->getGoodsDescription());
                        self::assertEquals(null, $dayStop->getGoodsDescriptionOther());
                        self::assertEquals(HazardousGoods::CODE_7_RADIOACTIVE_MATERIAL, $dayStop->getHazardousGoodsCode());
                        self::assertEquals(CargoType::CODE_PL_PALLETISED_GOODS, $dayStop->getCargoTypeCode());
                        self::assertEquals("7613", $dayStop->getWeightOfGoodsCarried());
                        self::assertEquals(true, $dayStop->getWasAtCapacity());
                        self::assertEquals(true, $dayStop->getWasLimitedByWeight());
                        self::assertEquals(false, $dayStop->getWasLimitedBySpace());
                    })
                ],
            ],
            'Change goods weight' => [
                '/goods-weight',
                [DayStopFixtures::class], // extra fixtures
                [
                    new WizardStepUrlTestCase(self::$baseUrl."/goods-weight", "goods_weight_button_group_continue", [
                        new FormTestCase([
                            "goods_weight" => [
                                "weightOfGoodsCarried" => null,
                                "wasAtCapacity" => "yes",
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
                                "wasAtCapacity" => "yes",
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

                        self::assertNotNull($dayStop);
                        self::assertEquals("8843", $dayStop->getWeightOfGoodsCarried());
                        self::assertEquals(true, $dayStop->getWasAtCapacity());
                        self::assertEquals(true, $dayStop->getWasLimitedByWeight());
                        self::assertEquals(true, $dayStop->getWasLimitedBySpace());
                    })
                ],
            ],
        ];
    }

    #[DataProvider('wizardData')]
    public function testDayStopEditWizards($startRelativeUrl, $fixtures, $wizardData)
    {
        $browser = $this->getBrowserLoadFixturesAndLogin($fixtures);
        $browser->request('GET', self::$baseUrl."{$startRelativeUrl}");

        $this->doWizardTest($browser, $wizardData);
    }
}