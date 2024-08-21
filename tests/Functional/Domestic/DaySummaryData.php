<?php

namespace App\Tests\Functional\Domestic;

use App\Entity\Distance;
use App\Tests\Functional\Wizard\FormTestCase;
use App\Tests\Functional\Wizard\WizardEndUrlTestCase;
use App\Tests\Functional\Wizard\WizardStepUrlTestCase;

class DaySummaryData extends AbstractDayStopOrSummaryData
{
    public static function daySummaryData(bool $goodsLoaded, ?bool $goodsUnloaded = null, ?bool $isHazardous = null): array
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
                        "hasMoreThanFiveStops" => "1",
                    ],
                ])
            ]),
            new WizardStepUrlTestCase("{$baseUrl}/summary/introduction", "form_continue", [
                new FormTestCase([], []),
            ]),
            new WizardStepUrlTestCase("{$baseUrl}/summary/day-start", "origin_continue", $originTests),
        ];

        $destinationUrl = "{$baseUrl}/summary/day-end";
        $furthestStopUrl = "{$baseUrl}/summary/furthest-stop";

        if ($goodsUnloaded === null) {
            return array_merge($testData, [new WizardStepUrlTestCase($destinationUrl)]);
        }

        $destinationTests = self::getLocationFormTestCases('destination', $goodsUnloaded, 'Worthing');
        $testData = array_merge($testData, [
            new WizardStepUrlTestCase($destinationUrl, "destination_continue", $destinationTests),
        ]);

        if ($isHazardous === null) {
            return array_merge($testData, [new WizardStepUrlTestCase($furthestStopUrl)]);
        }

        return array_merge($testData, [
            new WizardStepUrlTestCase($furthestStopUrl, "furthest_stop_continue", [
                new FormTestCase([], [
                    "#furthest_stop_furthestStop",
                ]),
                new FormTestCase([
                    "furthest_stop" => [
                        "furthestStop" => "The Moon",
                    ],
                ])
            ]),
            new WizardStepUrlTestCase("{$baseUrl}/summary/border-crossing", "border_crossing_continue", [
                new FormTestCase([
                    "border_crossing" => [
                        "borderCrossed" => 1,
                        "borderCrossingLocation" => "Hove", // N.B. This field can be left blank
                    ],
                ]),
            ]),
            new WizardStepUrlTestCase("{$baseUrl}/summary/distance-travelled", "distance_travelled_continue", [
                new FormTestCase([], [
                    "#distance_travelled_distanceTravelledLoaded_value",
                    "#distance_travelled_distanceTravelledLoaded_unit",
                    "#distance_travelled_distanceTravelledUnloaded_value",
                    "#distance_travelled_distanceTravelledUnloaded_unit",
                ]),
                new FormTestCase([
                    "distance_travelled" => [
                        "distanceTravelledLoaded" => [
                            "value" => 10,
                            "unit" => Distance::UNIT_MILES,
                        ],
                        "distanceTravelledUnloaded" => [
                            "value" => 16,
                            "unit" => Distance::UNIT_KILOMETRES,
                        ],
                    ]
                ])
            ]),
            new WizardStepUrlTestCase("{$baseUrl}/summary/goods-description", "goods_description_continue", [
                new FormTestCase([], ["#goods_description_goodsDescription"]),
                new FormTestCase([
                    "goods_description" => [
                        "goodsDescription" => "other-goods",
                    ],
                ], [
                    "#goods_description_goodsDescriptionOther"
                ]),
                new FormTestCase([
                    "goods_description" => [
                        "goodsDescription" => "other-goods",
                        "goodsDescriptionOther" => "Bread",
                    ]
                ])
            ]),
            new WizardStepUrlTestCase("{$baseUrl}/summary/hazardous-goods", "hazardous_goods_continue", self::getHazardousTests($isHazardous)),
            new WizardStepUrlTestCase("{$baseUrl}/summary/cargo-type", "cargo_type_continue", [
                new FormTestCase([], [
                    "#cargo_type_cargoTypeCode",
                ]),
                new FormTestCase([
                    "cargo_type" => [
                        "cargoTypeCode" => "RC",
                    ],
                ])
            ]),
            new WizardStepUrlTestCase("{$baseUrl}/summary/goods-weight", "goods_weight_continue", [
                new FormTestCase([], [
                    "#goods_weight_weightOfGoodsLoaded",
                    "#goods_weight_weightOfGoodsUnloaded",
                ]),
                new FormTestCase([
                    "goods_weight" => [
                        "weightOfGoodsLoaded" => 2000,
                        "weightOfGoodsUnloaded" => 1800,
                    ],
                ])
            ]),
            new WizardStepUrlTestCase("{$baseUrl}/summary/number-of-stops", "number_of_stops_continue", [
                new FormTestCase([], [
                    "#number_of_stops_group_numberOfStopsLoading",
                    "#number_of_stops_group_numberOfStopsUnloading",
                    "#number_of_stops_group_numberOfStopsLoadingAndUnloading",
                ]),
                // At least five stops...
                new FormTestCase([
                    "number_of_stops" => [
                        "group" => [
                            "numberOfStopsLoading" => 1,
                            "numberOfStopsUnloading" => 1,
                            "numberOfStopsLoadingAndUnloading" => 0,
                        ],
                    ],
                ], ["#number_of_stops_group"]),
                new FormTestCase([
                    "number_of_stops" => [
                        "group" => [
                            "numberOfStopsLoading" => 2,
                            "numberOfStopsUnloading" => 8,
                            "numberOfStopsLoadingAndUnloading" => 0,
                        ],
                    ],
                ]),
            ]),
            new WizardEndUrlTestCase($baseUrl),
        ]);
    }
}