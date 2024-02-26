<?php

namespace App\Tests\Functional\Domestic;

use App\Tests\Functional\Wizard\FormTestCase;
use App\Tests\Functional\Wizard\WizardEndUrlTestCase;
use App\Tests\Functional\Wizard\WizardStepUrlTestCase;

class DayStopData extends AbstractDayStopOrSummaryData
{
    public static function dayStopData(bool $goodsLoaded, ?bool $goodsUnloaded=null, ?bool $isEmpty=null, ?bool $isHazardous=null, ?bool $atCapacityBySpace=null, ?bool $atCapacityByWeight=null): array
    {
        $baseUrl = "/domestic-survey/day-1";

        $originTests = self::getLocationFormTestCases('origin', $goodsLoaded, 'Portsmouth');
        $testData = [
            new WizardStepUrlTestCase("{$baseUrl}/add", "number_of_stops_continue", [
                new FormTestCase([], [
                    "#number_of_stops_hasMoreThanFiveStops",
                ]),
                new FormTestCase([
                    "number_of_stops" => [
                        "hasMoreThanFiveStops" => "0",
                    ],
                ])
            ]),
            new WizardStepUrlTestCase("{$baseUrl}/stop-add/introduction", "form_continue", [
                new FormTestCase([], []),
            ]),
            new WizardStepUrlTestCase("{$baseUrl}/stop-add/stage-start", "origin_continue", $originTests),
        ];

        $destinationUrl = "{$baseUrl}/stop-add/stage-end";
        $borderCrossingUrl = "{$baseUrl}/stop-add/border-crossing";
        $hazardousGoodsUrl = "{$baseUrl}/stop-add/hazardous-goods";
        $goodsWeightUrl = "{$baseUrl}/stop-add/goods-weight";

        if ($goodsUnloaded === null) {
            return array_merge($testData, [new WizardStepUrlTestCase($destinationUrl)]);
        }

        $destinationTests = self::getLocationFormTestCases('destination', $goodsUnloaded, 'Worthing');
        $testData = array_merge($testData, [
            new WizardStepUrlTestCase($destinationUrl, "destination_continue", $destinationTests),
        ]);

        if ($isEmpty === null) {
            return array_merge($testData, [new WizardStepUrlTestCase($borderCrossingUrl)]);
        }

        $goodsDescriptionTests = self::getGoodsDescriptionTests($isEmpty);
        $testData = array_merge($testData, [
            new WizardStepUrlTestCase($borderCrossingUrl, "border_crossing_continue", [
                new FormTestCase([
                    "border_crossing" => [
                        "borderCrossed" => 1,
                        "borderCrossingLocation" => "Hove", // N.B. This field can be left blank
                    ],
                ]),
            ]),
            new WizardStepUrlTestCase("{$baseUrl}/stop-add/distance-travelled", "distance_travelled_continue", [
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
            new WizardStepUrlTestCase("{$baseUrl}/stop-add/goods-description", "goods_description_continue", $goodsDescriptionTests),
        ]);

        if (!$isEmpty) {
            if ($isHazardous === null) {
                return array_merge($testData, [new WizardStepUrlTestCase($hazardousGoodsUrl)]);
            }

            $hazardousTests = self::getHazardousTests($isHazardous);
            $testData = array_merge($testData, [
                new WizardStepUrlTestCase($hazardousGoodsUrl, "hazardous_goods_continue", $hazardousTests),
                new WizardStepUrlTestCase("{$baseUrl}/stop-add/cargo-type", "cargo_type_continue", [
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
                return array_merge($testData, [new WizardStepUrlTestCase($goodsWeightUrl)]);
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
                            "wasAtCapacity" => "no"
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
                            "wasAtCapacity" => "yes"
                        ],
                    ], [
                        "#goods_weight_wasLimitedBy",
                    ]),
                    new FormTestCase([
                        "goods_weight" => [
                            "weightOfGoodsCarried" => "100",
                            "wasAtCapacity" => "yes",
                            "wasLimitedBy" => $wasLimitedBy,
                        ],
                    ]),
                ]);
            }

            $testData = array_merge($testData, [
                new WizardStepUrlTestCase($goodsWeightUrl, "goods_weight_continue", $weightTests),
            ]);
        }

        return array_merge($testData, [
            new WizardEndUrlTestCase($baseUrl)
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