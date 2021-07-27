<?php

namespace App\Tests\Functional\Domestic;

use App\Tests\Functional\Wizard\FormTestCase;
use App\Tests\Functional\Wizard\WizardEndTestCase;
use App\Tests\Functional\Wizard\WizardStepTestCase;

class DaySummaryData extends AbstractDayStopOrSummaryData
{
    public static function daySummaryData(bool $goodsLoaded, ?bool $goodsUnloaded = null, ?bool $isHazardous = null): array
    {
        $originTests = self::getLocationFormTestCases('origin', $goodsLoaded, 'Portsmouth');
        $testData = [
            new WizardStepTestCase("How many times did you stop on day 1?", "number_of_stops_continue", [
                new FormTestCase([], [
                    "#number_of_stops_hasMoreThanFiveStops",
                ]),
                new FormTestCase([
                    "number_of_stops" => [
                        "hasMoreThanFiveStops" => "1",
                    ],
                ])
            ]),
            new WizardStepTestCase("Where did the vehicle start the day?", "origin_continue", $originTests),
        ];

        $destinationTitle = "Where did the journey end?";
        $furthestPointTitle = "Furthest point on this day";

        if ($goodsUnloaded === null) {
            return array_merge($testData, [new WizardStepTestCase($destinationTitle)]);
        }

        $destinationTests = self::getLocationFormTestCases('destination', $goodsUnloaded, 'Worthing');
        $testData = array_merge($testData, [
            new WizardStepTestCase($destinationTitle, "destination_continue", $destinationTests),
        ]);

        if ($isHazardous === null) {
            return array_merge($testData, [new WizardStepTestCase($furthestPointTitle)]);
        }

        return array_merge($testData, [
            new WizardStepTestCase($furthestPointTitle, "furthest_stop_continue", [
                new FormTestCase([], [
                    "#furthest_stop_furthestStop",
                ]),
                new FormTestCase([
                    "furthest_stop" => [
                        "furthestStop" => "The Moon",
                    ],
                ])
            ]),
            new WizardStepTestCase("Border crossing", "border_crossing_continue", [
                new FormTestCase([
                    "border_crossing" => [
                        "borderCrossingLocation" => "Hove", // N.B. This field can be left blank
                    ],
                ]),
            ]),
            new WizardStepTestCase("How far did you travel?", "distance_travelled_continue", [
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
                            "unit" => "miles",
                        ],
                        "distanceTravelledUnloaded" => [
                            "value" => 16,
                            "unit" => "kilometers",
                        ],
                    ]
                ])
            ]),
            new WizardStepTestCase("Description of goods carried", "goods_description_continue", [
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
            new WizardStepTestCase("Dangerous or hazardous goods", "hazardous_goods_continue", self::getHazardousTests($isHazardous)),
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
            new WizardStepTestCase("Weight of goods", "goods_weight_continue", [
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
            new WizardStepTestCase("Number of stops", "number_of_stops_continue", [
                new FormTestCase([], [
                    "#number_of_stops_numberOfStopsLoading",
                    "#number_of_stops_numberOfStopsUnloading",
                    "#number_of_stops_numberOfStopsLoadingAndUnloading",
                ]),
                new FormTestCase([
                    "number_of_stops" => [
                        "numberOfStopsLoading" => 2,
                        "numberOfStopsUnloading" => 8,
                        "numberOfStopsLoadingAndUnloading" => 0,
                    ],
                ]),
            ]),
            new WizardEndTestCase('Day record', WizardEndTestCase::MODE_CONTAINS),
        ]);
    }
}