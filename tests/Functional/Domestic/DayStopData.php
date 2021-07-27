<?php

namespace App\Tests\Functional\Domestic;

use App\Tests\Functional\Wizard\FormTestCase;
use App\Tests\Functional\Wizard\WizardEndTestCase;
use App\Tests\Functional\Wizard\WizardStepTestCase;

class DayStopData extends AbstractDayStopOrSummaryData
{
    public static function dayStopData(bool $goodsLoaded, ?bool $goodsUnloaded=null, ?bool $isEmpty=null, ?bool $isHazardous=null, ?bool $atCapacityBySpace=null, ?bool $atCapacityByWeight=null): array
    {
        $originTests = self::getLocationFormTestCases('origin', $goodsLoaded, 'Portsmouth');
        $testData = [
            new WizardStepTestCase("How many times did you stop on day 1?", "number_of_stops_continue", [
                new FormTestCase([], [
                    "#number_of_stops_hasMoreThanFiveStops",
                ]),
                new FormTestCase([
                    "number_of_stops" => [
                        "hasMoreThanFiveStops" => "0",
                    ],
                ])
            ]),
            new WizardStepTestCase("Where did the vehicle begin this stage of the journey?", "origin_continue", $originTests),
        ];

        $destinationTitle = "Where did this stage of the journey end?";
        $borderCrossingTitle = "Border crossing";
        $hazardousGoodsTitle = "Dangerous or hazardous goods";
        $goodsWeightTitle = "Goods weight and vehicle capacity";

        if ($goodsUnloaded === null) {
            return array_merge($testData, [new WizardStepTestCase($destinationTitle)]);
        }

        $destinationTests = self::getLocationFormTestCases('destination', $goodsUnloaded, 'Worthing');
        $testData = array_merge($testData, [
            new WizardStepTestCase($destinationTitle, "destination_continue", $destinationTests),
        ]);

        if ($isEmpty === null) {
            return array_merge($testData, [new WizardStepTestCase($borderCrossingTitle)]);
        }

        $goodsDescriptionTests = self::getGoodsDescriptionTests($isEmpty);
        $testData = array_merge($testData, [
            new WizardStepTestCase($borderCrossingTitle, "border_crossing_continue", [
                new FormTestCase([
                    "border_crossing" => [
                        "borderCrossingLocation" => "Hove", // N.B. This field can be left blank
                    ],
                ]),
            ]),
            new WizardStepTestCase("Distance travelled", "distance_travelled_continue", [
                new FormTestCase([], [
                    "#distance_travelled_distanceTravelled_value",
                    "#distance_travelled_distanceTravelled_unit",
                ]),
                new FormTestCase([
                    "distance_travelled" => [
                        "distanceTravelled" => [
                            "value" => 120,
                            "unit" => "miles"
                        ]
                    ]
                ])
            ]),
            new WizardStepTestCase("Description of goods carried", "goods_description_continue", $goodsDescriptionTests),
        ]);

        if (!$isEmpty) {
            if ($isHazardous === null) {
                return array_merge($testData, [new WizardStepTestCase($hazardousGoodsTitle)]);
            }

            $hazardousTests = self::getHazardousTests($isHazardous);
            $testData = array_merge($testData, [
                new WizardStepTestCase($hazardousGoodsTitle, "hazardous_goods_continue", $hazardousTests),
                new WizardStepTestCase("How were the goods carried?", "cargo_type_continue", [
                    new FormTestCase([], [
                        "#cargo_type_cargoTypeCode",
                    ]),
                    new FormTestCase([
                        "cargo_type" => [
                            "cargoTypeCode" => "RC",
                        ],
                    ])
                ]),

            ]);

            if ($atCapacityBySpace === null || $atCapacityByWeight === null) {
                return array_merge($testData, [new WizardStepTestCase($goodsWeightTitle)]);
            }

            $weightTests = [
                new FormTestCase([], [
                    "#goods_weight_weightOfGoodsCarried",
                    "#goods_weight_wasAtCapacity",
                ]),
            ];

            if (!$atCapacityByWeight && !$atCapacityBySpace) {
                $weightTests = array_merge($weightTests, [
                    new FormTestCase([
                        "goods_weight" => [
                            "weightOfGoodsCarried" => "100",
                            "wasAtCapacity" => "0"
                        ],
                    ])
                ]);
            } else {
                if ($atCapacityBySpace && !$atCapacityByWeight) {
                    $wasLimitedBy = [0 => "space"];
                } else if (!$atCapacityBySpace && $atCapacityByWeight) {
                    $wasLimitedBy = [1 => "weight"];
                } else {
                    $wasLimitedBy = [0 => "space", 1 => "weight"];
                }

                $weightTests = array_merge($weightTests, [
                    new FormTestCase([
                        "goods_weight" => [
                            "weightOfGoodsCarried" => "100",
                            "wasAtCapacity" => "1"
                        ],
                    ], [
                        "#goods_weight_wasLimitedBy",
                    ]),
                    new FormTestCase([
                        "goods_weight" => [
                            "weightOfGoodsCarried" => "100",
                            "wasAtCapacity" => "1",
                            "wasLimitedBy" => $wasLimitedBy,
                        ],
                    ]),
                ]);
            }

            $testData = array_merge($testData, [
                new WizardStepTestCase($goodsWeightTitle, "goods_weight_continue", $weightTests),
            ]);
        }

        return array_merge($testData, [
            new WizardEndTestCase('Day record', WizardEndTestCase::MODE_CONTAINS)
        ]);
    }

    protected static function getGoodsDescriptionTests(bool $isEmpty): array
    {
        $goodsDescriptionTests = [
            new FormTestCase([], ["#goods_description_goodsDescription"]),
        ];

        if ($isEmpty) {
            $goodsDescriptionTests[] = new FormTestCase([
                "goods_description" => [
                    "goodsDescription" => "empty",
                ],
            ]);
        } else {
            $goodsDescriptionTests[] = new FormTestCase([
                "goods_description" => [
                    "goodsDescription" => "other-goods",
                ],
            ], [
                "#goods_description_goodsDescriptionOther"
            ]);

            $goodsDescriptionTests[] = new FormTestCase([
                "goods_description" => [
                    "goodsDescription" => "other-goods",
                    "goodsDescriptionOther" => "Bread",
                ]
            ]);
        }

        return $goodsDescriptionTests;
    }
}